import { postJson } from './index';

/**
 * Module Schreiben — éditeur de texte avec compteur de mots, sauvegarde
 * automatique et verrouillage après expiration/validation.
 */
export function initWriting(cfg, setState) {
    const editor = document.getElementById('writing-editor');
    if (!editor) return;

    const counter = document.getElementById('word-count');
    const lockedNotice = document.getElementById('writing-locked');

    let locked = Boolean(cfg.exercise.locked);

    const countWords = (text) => (text.trim().match(/[\p{L}\p{N}]+/gu) || []).length;

    const updateCounter = () => {
        if (counter) counter.textContent = String(countWords(editor.value));
    };

    const setLocked = (value) => {
        locked = value;
        editor.readOnly = value;
        editor.classList.toggle('bg-slate-100', value);
        lockedNotice?.classList.toggle('hidden', !value);
    };

    editor.value = cfg.writing?.content ?? editor.value ?? '';
    updateCounter();
    if (cfg.writing?.locked) setLocked(true);

    let timer = null;
    const scheduleSave = () => {
        clearTimeout(timer);
        timer = setTimeout(() => persist(true), 1500);
    };

    async function persist(autosave) {
        const response = await postJson(cfg.endpoints.writing, {
            exercise_id: cfg.exercise.id,
            content: editor.value,
            autosave,
        });

        if (response.locked) {
            setLocked(true);
            setState?.('Temps écoulé : le texte est verrouillé.');
            return response;
        }

        if (counter) counter.textContent = String(response.word_count ?? countWords(editor.value));
        setState?.(autosave ? 'Brouillon enregistré' : 'Texte validé');

        return response;
    }

    editor.addEventListener('input', () => {
        if (locked) return;
        updateCounter();
        scheduleSave();
    });

    // Sauvegarde automatique périodique.
    setInterval(() => {
        if (!locked && editor.value.trim() !== '') persist(true);
    }, (cfg.autosaveInterval || 10) * 1000);

    window.__writingSubmit = async () => {
        if (locked) return;
        const response = await persist(false);
        if (response?.locked) setLocked(true);
        setLocked(true);
    };
}