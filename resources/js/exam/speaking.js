import { Recorder } from './recorder';

/**
 * Module Sprechen — préparation, enregistrement (MediaRecorder), aperçu,
 * nouvel enregistrement pendant la phase autorisée, envoi sécurisé.
 */
export function initSpeaking(cfg, timer, setState) {
    const panel = document.getElementById('speaking-panel');
    if (!panel) return;

    const preparationBlock = document.getElementById('speaking-preparation');
    const recordingBlock = document.getElementById('speaking-recording');
    const reviewBlock = document.getElementById('speaking-review');
    const prepCountdown = document.getElementById('prep-countdown');
    const recCountdown = document.getElementById('rec-countdown');
    const statusEl = document.getElementById('speaking-status');
    const errorEl = document.getElementById('speaking-error');
    const playback = document.getElementById('speaking-playback');

    const preparationSeconds = Number(cfg.exercise.preparation_seconds || 0);
    const recordingSeconds = Number(cfg.exercise.recording_seconds || 0)
        || Number(cfg.timer.duration_seconds || 120);

    let submitted = false;

    const recorder = new Recorder({
        maxSeconds: recordingSeconds,
        onTick: (elapsed, remaining) => {
            if (recCountdown) recCountdown.textContent = `${remaining}s`;
            if (remaining <= 0) setState?.('Fin de l’enregistrement.');
        },
        onStop: (blob) => {
            if (playback) {
                playback.src = URL.createObjectURL(blob);
                playback.classList.remove('hidden');
            }
            reviewBlock?.classList.remove('hidden');
            recordingBlock?.classList.add('hidden');
            if (statusEl) statusEl.textContent = 'Enregistrement terminé. Vous pouvez réécouter ou refaire.';
        },
        onError: (err) => {
            if (errorEl) {
                errorEl.textContent = err.message;
                errorEl.classList.remove('hidden');
            }
            setState?.('Microphone indisponible.');
        },
        onStateChange: (state) => {
            if (statusEl) statusEl.textContent = statusText(state);
        },
    });

    const statusText = (state) => ({
        granted: 'Microphone autorisé.',
        recording: 'Enregistrement en cours…',
        stopped: 'Arrêté.',
    }[state] || '');

    function startPreparationThenRecord() {
        if (preparationSeconds <= 0) {
            startRecording();
            return;
        }

        preparationBlock?.classList.remove('hidden');
        let remaining = preparationSeconds;

        const interval = setInterval(() => {
            remaining -= 1;
            if (prepCountdown) prepCountdown.textContent = `${Math.max(0, remaining)}s`;
            if (remaining <= 0) {
                clearInterval(interval);
                preparationBlock?.classList.add('hidden');
                startRecording();
            }
        }, 1000);
    }

    async function startRecording() {
        errorEl?.classList.add('hidden');
        reviewBlock?.classList.add('hidden');
        recordingBlock?.classList.remove('hidden');

        try {
            await recorder.start();
        } catch (e) {
            // L'erreur est déjà affichée par onError.
        }
    }

    // Démarre automatiquement (préparation -> enregistrement) au chargement.
    startPreparationThenRecord();

    // Refaire un enregistrement (phase autorisée uniquement).
    document.getElementById('btn-record-again')?.addEventListener('click', () => {
        if (submitted || timer.remaining() <= 0) return;
        recorder.reset();
        startRecording();
    });

    // Fourni à l'orchestrateur pour stopper lors de l'expiration.
    window.__speakingStop = () => recorder.stop();

    // Fourni pour la validation (WEITER) : envoi définitif.
    window.__speakingSubmit = async () => {
        if (submitted) return;
        if (!recorder.blob) {
            setState?.('Aucun enregistrement à envoyer.');
            return;
        }

        const url = cfg.endpoints.speaking;
        const response = await recorder.upload(url, {
            exercise_id: cfg.exercise.id,
            duration_seconds: recordingSeconds,
            submit: 1,
        });

        if (response.locked) {
            setState?.('Temps écoulé : envoi refusé.');
            return;
        }

        submitted = true;
        recorder.release();
        setState?.('Enregistrement envoyé. Analyse en cours…');
    };
}