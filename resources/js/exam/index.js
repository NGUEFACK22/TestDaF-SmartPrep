import { ServerTimer, QuestionTimer } from './timer';
import { renderQuestion } from './questionRenderer';

/**
 * Orchestrateur de la page d'examen.
 *
 * Le serveur reste l'autorité (temps, états, verrouillage). Ce module gère
 * uniquement l'interaction : rendu des questions, sauvegarde automatique,
 * synchronisation du chronomètre, validation / expiration.
 */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/**
 * POST JSON avec timeout (30 s) : une requête qui traîne (cold start base,
 * réseau) ne laisse jamais le bouton SUIVANT bloqué à l'infini. Le catch de
 * l'appelant redirige vers exam.show où le serveur restaure l'état.
 */
export async function postJson(url, payload, timeoutMs = 30000) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload || {}),
            signal: controller.signal,
        });

        return await response.json();
    } finally {
        clearTimeout(timer);
    }
}

function debounce(fn, delay) {
    let handle = null;
    return (...args) => {
        clearTimeout(handle);
        handle = setTimeout(() => fn(...args), delay);
    };
}

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.__EXAM__;
    if (cfg) {
        init(cfg);
    }
});

function init(cfg) {
    const root = document.querySelector('[data-exam-root]');
    if (!root) return;

    const timer = new ServerTimer(root);
    timer.sync(cfg.timer);

    const stateEl = document.getElementById('save-state');
    const setState = (text) => { if (stateEl) stateEl.textContent = text; };

    // ------------------------------------------------ Rendu des questions
    // Mode chronométré : le serveur ne fournit QUE la question en cours ;
    // aucune question future n'est exposée au client (anti-cheat).
    const timed = !!(cfg.questionTimer && cfg.questionTimer.enabled);
    const container = document.getElementById('questions-container');
    let blocks = [];
    let currentQuestion = null;
    // ?? et non || : l'index 0 est une valeur valide (|| le remplaçait
    // par 0 de toute façon, mais écrasait aussi NaN/'' sans prévenir).
    let questionIndex = Number(cfg.currentQuestionIndex ?? 0);
    let questionTotal = Number(cfg.totalQuestions ?? cfg.questions?.length ?? 1);
    // Déclaré ICI (en haut) : renderCurrentQuestion() y accède dès le premier
    // rendu — un `let` plus bas provoquerait une erreur de zone morte (TDZ)
    // qui tuerait toute la page (bouton SUIVANT mort, pastille figée).
    let questionTimer = null;

    function updateProgress() {
        const progress = document.getElementById('question-progress');
        if (progress) progress.textContent = `${questionIndex + 1} / ${questionTotal}`;
        const btn = document.getElementById('btn-weiter');
        if (btn && timed) btn.textContent = (questionIndex < questionTotal - 1) ? 'SUIVANT' : 'WEITER';
    }

    function warn(text, sticky = false) {
        setState(text);
        const w = document.getElementById('answer-warning');
        if (w) {
            w.textContent = text;
            w.classList.remove('hidden');
            clearTimeout(w._t);
            if (!sticky) {
                w._t = setTimeout(() => w.classList.add('hidden'), 6000);
            }
        }
    }

    // Verrouille visuellement un bloc dont le temps est écoulé : les boutons
    // radio/cases/champs sont désactivés pour qu'on ne puisse plus croire
    // qu'une réponse sera prise en compte (le serveur la refuserait en 423).
    function lockBlock(el, message) {
        if (!el || el.dataset.locked === '1') return;
        el.dataset.locked = '1';
        el.classList.add('opacity-60');
        el.querySelectorAll('input, textarea, select, button').forEach((n) => {
            n.disabled = true;
        });
        const badge = el.querySelector('[data-role="answer-status"]');
        if (badge) {
            badge.textContent = '⏱ Temps écoulé';
            badge.className = 'rounded-full bg-red-100 text-red-800 px-2.5 py-0.5 font-medium';
        }
        if (message) {
            warn(message, true);
        }
    }

    // Échecs de sauvegarde consécutifs sur le même bloc : au-delà de 2, on
    // verrouille le bloc au lieu de spammer le serveur toutes les 10 s.
    let lockStrikes = 0;

    function renderCurrentQuestion(question, index = questionIndex, total = questionTotal) {
        if (!container || !question) return;
        const existing = cfg.answers ? cfg.answers[question.id] ?? null : null;
        const el = renderQuestion(question, existing, { index, total });
        container.replaceChildren(el);
        currentQuestion = question;
        blocks = [{ question, el }];
        lockStrikes = 0;
        updateProgress();
        refreshBadges();
        // Question déjà expirée côté serveur (reprise après coupure, onglet
        // resté ouvert) : verrouiller tout de suite au lieu de laisser croire
        // qu'on peut encore répondre.
        if (timed && questionTimer && questionTimer.remaining() === 0 && questionTimer.active) {
            lockBlock(el, '⏱ Temps écoulé pour cette Frage — cliquez SUIVANT pour passer à la suivante (0 point).');
        }
    }

    if (container && Array.isArray(cfg.questions) && cfg.questions.length > 0) {
        if (timed) {
            renderCurrentQuestion(cfg.questions[0], questionIndex, questionTotal);
        } else {
            // Mode non chronométré : TOUTES les questions numérotées 1..N.
            questionTotal = cfg.questions.length;
            cfg.questions.forEach((question, i) => {
                const existing = cfg.answers ? cfg.answers[question.id] ?? null : null;
                const el = renderQuestion(question, existing, { index: i, total: cfg.questions.length });
                container.appendChild(el);
                blocks.push({ question, el });
            });
            questionIndex = 0;
        }
    }

    // Une réponse "vide" (null, '', [], {}, '—') n'est JAMAIS envoyée :
    // seules les réponses réellement saisies sont sauvegardées.
    const isEmptyAnswer = (answer) => {
        if (answer === null || answer === undefined) return true;
        if (typeof answer === 'string' && answer.trim() === '') return true;
        if (Array.isArray(answer)) {
            const filled = answer.filter((v) => String(v ?? '').trim() !== '');
            return filled.length === 0;
        }
        if (typeof answer === 'object') {
            const vals = Object.values(answer);
            if (vals.length === 0) return true;
            return vals.every((v) => String(v ?? '').trim() === '');
        }
        return false;
    };

    const currentIsAnswered = () => {
        if (blocks.length === 0) return false;
        try {
            return !isEmptyAnswer(blocks[0].el._read());
        } catch (e) {
            return false;
        }
    };

    // Pastille verte/grise par question : le candidat voit immédiatement
    // ce qui est répondu et ce qui ne l'est pas (fini la confusion avec
    // le texte d'aide gris).
    function refreshBadges() {
        blocks.forEach(({ el }) => {
            let answered = false;
            try {
                answered = !isEmptyAnswer(el._read());
            } catch (e) {
                answered = false;
            }
            const badge = el.querySelector('[data-role="answer-status"]');
            if (badge) {
                badge.textContent = answered ? '✓ Répondu' : '· En attente';
                badge.className = answered
                    ? 'rounded-full bg-green-100 text-green-800 px-2.5 py-0.5 font-medium'
                    : 'rounded-full bg-slate-100 text-slate-500 px-2.5 py-0.5 font-medium';
            }
            if (answered) {
                el.classList.remove('ring-2', 'ring-red-400');
            }
        });
    }

    const collect = () => blocks
        .map(({ question, el }) => ({ question_id: question.id, answer: el._read() }))
        .filter(({ answer }) => !isEmptyAnswer(answer));

    // ------------------------------------------------ Sauvegarde automatique
    let saving = false;
    let dirty = false;
    let saveInFlight = null;

    async function saveAll(autosave = true) {
        if (!cfg.endpoints.answers || blocks.length === 0) return;

        if (saving) {
            dirty = true;
            // Un flush explicite (SUIVANT / expiration) ne doit PAS se contenter
            // de marquer "dirty" : il attend l'auto-sauvegarde en cours, puis
            // re-sauve pour que la réponse venant d'être saisie ne soit pas
            // perdue au moment où la question va être verrouillée.
            if (autosave !== false) {
                return;
            }
            if (saveInFlight) {
                try { await saveInFlight; } catch (e) { /* ignoré */ }
            }
            return saveAll(false);
        }

        saving = true;
        setState(autosave ? 'Enregistrement…' : 'Validation…');

        saveInFlight = (async () => {
            try {
                const answers = collect();
                const results = await Promise.all(
                    answers.map(({ question_id, answer }) =>
                        postJson(cfg.endpoints.answers, { question_id, answer, autosave })
                    )
                );

                const locked = results.find((r) => r && r.locked);
                if (locked) {
                    // Verrou (temps écoulé) : le serveur reste l'autorité.
                    // On affiche la raison CLAIREMENT (fini le silence) puis on
                    // resynchronise avec /timer qui renvoie la question réelle.
                    // Anti-spam : après 2 refus de suite, on verrouille le bloc
                    // au lieu de retenter toutes les 10 s indéfiniment.
                    lockStrikes += 1;
                    if (timed && locked.reason === 'question_locked') {
                        const el = blocks[0]?.el;
                        if (lockStrikes >= 2 && el) {
                            lockBlock(el, '⏱ Temps écoulé pour cette Frage — réponse refusée par le serveur. Cliquez SUIVANT pour continuer.');
                        } else {
                            warn('⏱ Temps écoulé pour cette Frage — réponse non enregistrée (0 point). Cliquez SUIVANT.', true);
                        }
                        await resync();
                        return;
                    }
                    warn('⏱ Temps écoulé pour cette tâche — redirection…', true);
                    setState('Temps écoulé');
                    window.location = cfg.endpoints.redirect;
                    return;
                }

                lockStrikes = 0;
                setState(autosave ? `Sauvegardé à ${new Date().toLocaleTimeString()}` : 'Enregistré');
            } catch (e) {
                setState('Erreur de sauvegarde — nouvelle tentative…');
            } finally {
                saving = false;
                saveInFlight = null;
                if (dirty) { dirty = false; saveAll(autosave); }
            }
        })();

        await saveInFlight;
    }

    if (blocks.length > 0) {
        setInterval(() => saveAll(true), (cfg.autosaveInterval || 10) * 1000);
        container.addEventListener('change', () => { refreshBadges(); saveAll(true); });
        container.addEventListener('input', debounce(() => { refreshBadges(); saveAll(true); }, 1200));
        refreshBadges();
    }

    // ------------------------------------------------ Finalisation serveur
    async function complete(reason) {
        try {
            const result = await postJson(cfg.endpoints.complete, { reason });
            // Le serveur indique TOUJOURS où aller : partie suivante (page
            // d'examen) ou résultats (dernière partie vraiment terminée).
            window.location = result?.redirect || result?.next?.redirect || cfg.endpoints.redirect;
        } catch (e) {
            window.location = cfg.endpoints.redirect;
        }
    }

    async function expire() {
        setState('Le temps est écoulé.');
        document.getElementById('expired-banner')?.classList.remove('hidden');

        // Verrouille tous les blocs : plus aucune saisie ne sera acceptée
        // par le serveur, inutile de laisser les champs actifs.
        blocks.forEach(({ el }) => lockBlock(el, null));
        warn('⏱ Temps écoulé pour cette tâche — vos dernières réponses sont sauvegardées, redirection…', true);

        await saveAll(true);
        await complete('expired');
    }

    // ------------------------------------------------ Chronomètre + resync
    timer.onExpire = expire;
    timer.start();

    // --------------------------------------------- Timer par question
    if (timed && cfg.endpoints.next) {
        questionTimer = new QuestionTimer({
            label: document.querySelector('[data-question-timer-label]'),
            bar: document.querySelector('[data-question-timer-bar]'),
            warningSeconds: 10,
        });
        questionTimer.sync(cfg.questionTimer);
        questionTimer.onExpire = () => {
            // La question est morte : on verrouille les champs aussitôt pour
            // qu'aucune saisie ne laisse croire qu'elle sera enregistrée.
            if (blocks[0]?.el) {
                lockBlock(blocks[0].el, '⏱ Temps écoulé pour cette Frage — passage à la suivante…');
            }
            if (!advancing) advance();
        };
        questionTimer.start();
    }

    // ------------------------------------------------ Resynchronisation serveur
    // Le serveur est l'autorité (temps, question courante). En cas de doute
    // (doublon verrouillé, requête en retard), on reprend l'état réel via
    // l'endpoint /timer : cela n'avance JAMAIS par lui-même.
    let resyncing = false;

    async function resync() {
        if (!cfg.endpoints.timer || resyncing) return;
        resyncing = true;
        try {
            // Timeout court : un GET qui traîne ne doit pas bloquer les
            // prochaines resynchronisations (elles se répètent toutes les 15 s).
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 20000);
            let response;
            try {
                response = await fetch(cfg.endpoints.timer, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
            } finally {
                clearTimeout(timer);
            }
            const data = await response.json();

            if (data.finished) {
                window.location = data.redirect || cfg.endpoints.redirect;
                return;
            }
            if (data.timer) timer.sync(data.timer);
            if (timed && questionTimer && data.question_timer) {
                questionTimer.sync(data.question_timer);
                questionTimer.start();
                questionIndex = Number(data.question_timer.index ?? questionIndex);
                questionTotal = Number(data.question_timer.total ?? questionTotal);
                // Le serveur a fait avancer la question (temps dépassé) :
                // on affiche la nouvelle question sans recharger la page,
                // avec un message explicite (plus de "saut" mystérieux).
                if (data.question && currentQuestion && data.question.id !== currentQuestion.id) {
                    renderCurrentQuestion(data.question, questionIndex, questionTotal);
                    setState('⏱ Temps écoulé — question suivante');
                } else {
                    updateProgress();
                }
            }
        } catch (e) { /* silencieux : le serveur reste l'autorité */ } finally {
            resyncing = false;
        }
    }

    if (cfg.endpoints.timer) {
        setInterval(() => resync(), 15000);
    }

    // --------------------------------------------- Avancer à la question suivante
    let advancing = false;

    // Fenêtre de confirmation : affiche le temps restant + l'état de la
    // réponse, et laisse le candidat décider (Oui = continuer, Non = rester).
    // Retourne true si le candidat confirme.
    function confirmAdvance() {
        let remaining = 0;
        try {
            remaining = timer.remaining();
        } catch (e) {
            remaining = 0;
        }
        // Temps déjà écoulé (tâche ou question) : pas de fenêtre, on avance
        // directement — demander confirmation n'aurait aucun sens.
        try {
            if (remaining <= 0) return true;
            if (timed && questionTimer && questionTimer.active && questionTimer.remaining() === 0) return true;
        } catch (e) { /* en cas de doute, on affiche la fenêtre */ }
        const mm = Math.floor(remaining / 60);
        const ss = remaining % 60;
        const last = questionIndex >= questionTotal - 1;
        const answered = currentIsAnswered();

        let msg = `⏱ Il reste ${mm} min ${ss} s sur cette tâche.\n`;
        msg += answered
            ? 'Votre réponse est enregistrée.'
            : `⚠️ Frage ${questionIndex + 1} / ${questionTotal} SANS réponse (0 point).`;
        msg += `\n${last ? 'WEITER va clôturer définitivement la tâche' : 'Passer à la question suivante'} — continuer vraiment ?`;

        return window.confirm(msg);
    }

    async function advance() {
        if (!cfg.endpoints.next || advancing) return;

        advancing = true;
        setState('Validation…');

        try {
            // Sauvegarde de la réponse en cours avant l'avancement serveur.
            await saveAll(false);
            // from_index = question affichée par le client : le serveur s'en
            // sert pour éviter un double avancement (idempotence).
            const result = await postJson(cfg.endpoints.next, { from_index: questionIndex });

            if (!result || result.ok === false || result.redirect) {
                window.location = result?.redirect || cfg.endpoints.redirect;
                return;
            }

            // 'resync' : le serveur était déjà sur une question plus avancée —
            // on affiche celle-ci sans avancer davantage.
            if ((result.outcome === 'advanced' || result.outcome === 'resync') && result.question) {
                questionIndex = Number(result.question_timer?.index ?? questionIndex + 1);
                questionTotal = Number(result.question_timer?.total ?? questionTotal);
                renderCurrentQuestion(result.question, questionIndex, questionTotal);
                if (result.question_timer && questionTimer) {
                    questionTimer.sync(result.question_timer);
                    questionTimer.start();
                }
                if (result.skipped) {
                    warn(`⏭ Frage ${questionIndex} / ${questionTotal} passée sans réponse (0 point).`);
                } else {
                    setState('Question enregistrée');
                }
                return;
            }

            // Réponse inattendue (ni redirection, ni question) : on recharge
            // l'examen — le serveur restaure l'état autoritaire.
            window.location = cfg.endpoints.redirect;
        } catch (e) {
            window.location = cfg.endpoints.redirect;
        } finally {
            advancing = false;
        }
    }

    // ------------------------------------------------ Bouton WEITER / SUIVANT
    const weiter = document.getElementById('btn-weiter');
    if (weiter) {
        if (timed) {
            // Une question à la fois : SUIVANT (WEITER sur la dernière).
            weiter.textContent = (questionIndex < questionTotal - 1) ? 'SUIVANT' : 'WEITER';
        }

        weiter.addEventListener('click', async () => {
            if (timed) {
                // Fenêtre unique : temps restant + état de la réponse.
                // Oui = on avance, Non = on reste (rien n'est bloqué).
                if (!confirmAdvance()) {
                    return;
                }
                weiter.disabled = true;
                try {
                    await advance();
                } finally {
                    weiter.disabled = false;
                }
                return;
            }

            // Mode non chronométré : UNE seule fenêtre avec le temps restant
            // + le nombre de questions sans réponse. Oui = valider, Non = rester.
            const unanswered = blocks.filter(({ el }) => {
                try {
                    return isEmptyAnswer(el._read());
                } catch (e) {
                    return true;
                }
            });
            unanswered.forEach(({ el }) => el.classList.remove('ring-2', 'ring-red-400'));

            let remaining = 0;
            try {
                remaining = timer.remaining();
            } catch (e) {
                remaining = 0;
            }
            const mm = Math.floor(remaining / 60);
            const ss = remaining % 60;
            let msg = `⏱ Il reste ${mm} min ${ss} s sur cette tâche (le temps restant sera perdu, retour impossible).\n`;
            msg += unanswered.length > 0
                ? `⚠️ ${unanswered.length} question(s) SANS réponse (0 point).\nValider définitivement ?`
                : 'Toutes les questions ont une réponse.\nValider définitivement ?';
            if (!window.confirm(msg)) {
                if (unanswered.length > 0) {
                    unanswered.forEach(({ el }) => el.classList.add('ring-2', 'ring-red-400'));
                    unanswered[0]?.el?.querySelector('input, textarea, select')?.focus?.();
                }
                return;
            }

            weiter.disabled = true;
            setState('Validation…');

            await saveAll(false);
            await complete('manual');
        });
    }

    // ------------------------------------------------ Proctoring léger (informatif)
    // Journalise changements d'onglet / perte focus / copier-coller.
    // Ne bloque jamais : le serveur reste l'autorité, l'audit est en exam_logs.
    if (cfg.endpoints.event) {
        const sendEvent = (event) => {
            try {
                fetch(cfg.endpoints.event, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ event, exercise_id: cfg.exercise?.id ?? null }),
                    keepalive: true,
                }).catch(() => {});
            } catch (e) { /* silencieux */ }
        };

        let lastHidden = 0;
        document.addEventListener('visibilitychange', () => {
            const now = Date.now();
            if (document.hidden) {
                lastHidden = now;
                sendEvent('tab_hidden');
            } else {
                // N'alerte que les absences >3s (évite le bruit des alt-tab brefs).
                if (now - lastHidden > 3000) sendEvent('tab_visible');
            }
        });
        window.addEventListener('blur', () => sendEvent('focus_lost'));
        document.addEventListener('paste', () => sendEvent('paste'));
        document.addEventListener('copy', () => sendEvent('copy'));
    }

}