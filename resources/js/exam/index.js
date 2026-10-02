import { ServerTimer } from './timer';
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
    const container = document.getElementById('questions-container');
    const blocks = [];

    if (container) {
        (cfg.questions || []).forEach((question) => {
            const existing = cfg.answers ? cfg.answers[question.id] ?? null : null;
            const el = renderQuestion(question, existing);
            container.appendChild(el);
            blocks.push({ question, el });
        });
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
            } catch (e) { /* silencieux : le serveur reste l'autorité */ }
        }, 15000);
    }

    // ------------------------------------------------ Bouton WEITER
    const weiter = document.getElementById('btn-weiter');
    if (weiter) {
        weiter.addEventListener('click', async () => {
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