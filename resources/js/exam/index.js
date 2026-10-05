import { ServerTimer, QuestionTimer } from './timer';
import { renderQuestion } from './questionRenderer';
import { initWriting } from './writing';
import { initSpeaking } from './speaking';

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

export async function postJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload || {}),
    });

    return response.json();
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
    let questionIndex = cfg.currentQuestionIndex || 0;
    let questionTotal = cfg.totalQuestions || 1;

    function renderCurrentQuestion(question) {
        if (!container || !question) return;
        const existing = cfg.answers ? cfg.answers[question.id] ?? null : null;
        const el = renderQuestion(question, existing);
        container.replaceChildren(el);
        currentQuestion = question;
        blocks = [{ question, el }];
    }

    if (container && Array.isArray(cfg.questions) && cfg.questions.length > 0) {
        if (timed) {
            renderCurrentQuestion(cfg.questions[0]);
        } else {
            cfg.questions.forEach((question) => {
                const existing = cfg.answers ? cfg.answers[question.id] ?? null : null;
                const el = renderQuestion(question, existing);
                container.appendChild(el);
                blocks.push({ question, el });
            });
        }
    }

    const collect = () => blocks.map(({ question, el }) => ({
        question_id: question.id,
        answer: el._read(),
    }));

    // ------------------------------------------------ Sauvegarde automatique
    let saving = false;
    let dirty = false;

    async function saveAll(autosave = true) {
        if (!cfg.endpoints.answers || blocks.length === 0) return;

        if (saving) { dirty = true; return; }
        saving = true;
        setState('Enregistrement…');

        try {
            const answers = collect();
            const results = await Promise.all(
                answers.map(({ question_id, answer }) =>
                    postJson(cfg.endpoints.answers, { question_id, answer, autosave }))
            );

            const locked = results.find((r) => r && r.locked);
            if (locked) {
                // Verrou de question (temps de la question écoulé) : on demande
                // au serveur de faire avancer (pas de sortie de l'examen).
                if (timed && locked.reason === 'question_locked') {
                    setState('Question clôturée…');
                    advance();
                    return;
                }
                setState('Temps écoulé');
                window.location = cfg.endpoints.redirect;
                return;
            }

            setState(autosave ? `Sauvegardé à ${new Date().toLocaleTimeString()}` : 'Enregistré');
        } catch (e) {
            setState('Erreur de sauvegarde — nouvelle tentative…');
        } finally {
            saving = false;
            if (dirty) { dirty = false; saveAll(autosave); }
        }
    }

    if (blocks.length > 0) {
        setInterval(() => saveAll(true), (cfg.autosaveInterval || 10) * 1000);
        container.addEventListener('change', () => saveAll(true));
        container.addEventListener('input', debounce(() => saveAll(true), 1200));
    }

    // ------------------------------------------------ Finalisation serveur
    async function complete(reason) {
        try {
            const result = await postJson(cfg.endpoints.complete, { reason });
            window.location = result.redirect || cfg.endpoints.redirect;
        } catch (e) {
            window.location = cfg.endpoints.redirect;
        }
    }

    async function expire() {
        setState('Le temps est écoulé.');
        document.getElementById('expired-banner')?.classList.remove('hidden');

        if (cfg.exercise.skill === 'sprechen' && window.__speakingStop) {
            window.__speakingStop();
        }

        await saveAll(true);
        await complete('expired');
    }

    // ------------------------------------------------ Chronomètre + resync
    timer.onExpire = expire;
    timer.start();

    // --------------------------------------------- Timer par question
    let questionTimer = null;

    if (timed && cfg.endpoints.next) {
        questionTimer = new QuestionTimer({
            label: document.querySelector('[data-question-timer-label]'),
            bar: document.querySelector('[data-question-timer-bar]'),
            warningSeconds: 10,
        });
        questionTimer.sync(cfg.questionTimer);
        questionTimer.onExpire = () => { if (!advancing) advance(); };
        questionTimer.start();
    }

    if (cfg.endpoints.timer) {
        setInterval(async () => {
            try {
                const response = await fetch(cfg.endpoints.timer, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();

                if (data.finished) {
                    window.location = data.redirect || cfg.endpoints.redirect;
                    return;
                }
                if (data.timer) timer.sync(data.timer);
                if (timed && questionTimer && data.question_timer) {
                    questionTimer.sync(data.question_timer);
                    questionTimer.start();
                    questionIndex = data.question_timer.index ?? questionIndex;
                    questionTotal = data.question_timer.total ?? questionTotal;
                    const progress = document.getElementById('question-progress');
                    if (progress) progress.textContent = `${questionIndex + 1} / ${questionTotal}`;
                    // Le serveur a fait avancer la question (temps dépassé) :
                    // on affiche la nouvelle question sans recharger la page.
                    if (data.question && currentQuestion && data.question.id !== currentQuestion.id) {
                        renderCurrentQuestion(data.question);
                    }
                }
            } catch (e) { /* silencieux : le serveur reste l'autorité */ }
        }, 15000);
    }

    // --------------------------------------------- Avancer à la question suivante
    let advancing = false;

    async function advance() {
        if (!cfg.endpoints.next || advancing) return;
        advancing = true;
        setState('Validation…');

        try {
            // Sauvegarde de la réponse en cours avant l'avancement serveur.
            await saveAll(false);
            const result = await postJson(cfg.endpoints.next, {});

            if (!result || result.ok === false || result.redirect) {
                window.location = result?.redirect || cfg.endpoints.redirect;
                return;
            }

            if (result.outcome === 'advanced' && result.question) {
                questionIndex = result.question_timer?.index ?? questionIndex + 1;
                questionTotal = result.question_timer?.total ?? questionTotal;
                renderCurrentQuestion(result.question);
                if (result.question_timer && questionTimer) {
                    questionTimer.sync(result.question_timer);
                    questionTimer.start();
                }
                const progress = document.getElementById('question-progress');
                if (progress) progress.textContent = `${questionIndex + 1} / ${questionTotal}`;
                const btn = document.getElementById('btn-weiter');
                if (btn) btn.textContent = (questionIndex < questionTotal - 1) ? 'SUIVANT' : 'WEITER';
                setState('Question enregistrée');
            }
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
                // Sauvegarde de la réponse + avancement côté serveur
                // (le serveur clôture la tâche sur la dernière question).
                weiter.disabled = true;
                await advance();
                return;
            }

            weiter.disabled = true;
            setState('Validation…');

            if (cfg.exercise.skill === 'schreiben' && window.__writingSubmit) {
                await window.__writingSubmit();
            }

            if (cfg.exercise.skill === 'sprechen' && window.__speakingSubmit) {
                await window.__speakingSubmit();
            } else {
                await saveAll(false);
            }

            await complete('manual');
        });
    }

    // ------------------------------------------------ Modules spécifiques
    if (cfg.exercise.skill === 'schreiben') {
        initWriting(cfg, setState);
    }

    if (cfg.exercise.skill === 'sprechen') {
        initSpeaking(cfg, timer, setState);
    }
}