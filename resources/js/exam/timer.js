/**
 * ServerTimer — chronomètre d'affichage calé sur l'heure SERVEUR.
 *
 * Le temps est calculé à partir de expires_at fourni par le serveur, corrigé
 * d'un décalage (offset) entre l'heure serveur et l'heure locale. Modifier
 * l'horloge du navigateur n'a donc aucun effet sur le temps réel restant.
 * Le serveur reste l'autorité : ce module ne fait qu'afficher.
 */
export class ServerTimer {
    constructor(root) {
        this.root = root;
        this.bar = root.querySelector('[data-timer-bar]');
        this.label = root.querySelector('[data-timer-label]');
        this.warningSeconds = Number(root.dataset.warning || 30);
        this.onExpire = null;
        this.onTick = null;

        this.sync({
            expires_at: root.dataset.expiresAt,
            server_now: root.dataset.serverNow,
            duration_seconds: Number(root.dataset.duration || 0),
        });

        this._interval = null;
        this._tick = this._tick.bind(this);
    }

    sync(data) {
        if (data.duration_seconds !== undefined) {
            this.duration = Number(data.duration_seconds) || 0;
        }

        if (data.server_now) {
            const serverNow = new Date(data.server_now).getTime();
            this.offset = serverNow - Date.now();
        } else if (this.offset === undefined) {
            this.offset = 0;
        }

        if (data.expires_at) {
            this.expiresAt = new Date(data.expires_at).getTime();
        } else {
            this.expiresAt = null;
        }
    }

    serverTime() {
        return Date.now() + (this.offset || 0);
    }

    remaining() {
        if (!this.expiresAt) {
            return this.duration;
        }

        return Math.max(0, Math.round((this.expiresAt - this.serverTime()) / 1000));
    }

    start() {
        if (this._interval) return;
        this._tick();
        this._interval = setInterval(this._tick, 500);
    }

    stop() {
        clearInterval(this._interval);
        this._interval = null;
    }

    _tick() {
        const remaining = this.remaining();
        const percent = this.duration > 0 ? Math.min(100, (remaining / this.duration) * 100) : 0;

        if (this.label) {
            const m = String(Math.floor(remaining / 60)).padStart(2, '0');
            const s = String(remaining % 60).padStart(2, '0');
            this.label.textContent = `${m}:${s}`;
        }

        if (this.bar) {
            this.bar.style.width = `${percent}%`;
            this.bar.classList.toggle('warning', remaining <= this.warningSeconds && remaining > 10);
            this.bar.classList.toggle('danger', remaining <= 10);
        }

        if (this.onTick) this.onTick(remaining);

        if (remaining <= 0) {
            this.stop();
            if (this.onExpire) this.onExpire();
        }
    }
}

/**
 * QuestionTimer — chronomètre de question (affichage uniquement).
 *
 * Même logique d'horloge calée sur le serveur que ServerTimer, mais pour la
 * question en cours : expires_at plafonné côté serveur, le JS n'a aucun
 * pouvoir de prolongation. À zéro, le module notifie l'orchestrateur qui
 * demande au serveur d'avancer (question suivante ou fin de tâche).
 */
export class QuestionTimer {
    constructor({ label = null, bar = null, warningSeconds = 10 } = {}) {
        this.label = label;
        this.bar = bar;
        this.warningSeconds = warningSeconds;
        this.onExpire = null;
        this.onTick = null;

        this.offset = 0;
        this.expiresAt = null;
        this.duration = 0;
        this.active = false;

        this._interval = null;
        this._tick = this._tick.bind(this);
    }

    sync(data) {
        if (!data || !data.expires_at) {
            this.active = false;
            return;
        }

        if (data.server_now) {
            this.offset = new Date(data.server_now).getTime() - Date.now();
        }

        this.duration = Number(data.duration_seconds) || 0;
        this.expiresAt = new Date(data.expires_at).getTime();
        this.active = true;
    }

    serverTime() {
        return Date.now() + (this.offset || 0);
    }

    remaining() {
        if (!this.active || !this.expiresAt) return -1;
        return Math.max(0, Math.round((this.expiresAt - this.serverTime()) / 1000));
    }

    start() {
        if (!this.active || this._interval) return;
        this._tick();
        this._interval = setInterval(this._tick, 500);
    }

    stop() {
        clearInterval(this._interval);
        this._interval = null;
    }

    _tick() {
        const remaining = this.remaining();
        if (remaining < 0) return;

        if (this.label) {
            const m = String(Math.floor(remaining / 60)).padStart(2, '0');
            const s = String(remaining % 60).padStart(2, '0');
            this.label.textContent = `${m}:${s}`;
        }

        if (this.bar) {
            const percent = this.duration > 0
                ? Math.min(100, (remaining / this.duration) * 100)
                : 0;
            this.bar.style.width = `${percent}%`;
            this.bar.classList.toggle('warning', remaining <= this.warningSeconds && remaining > 5);
            this.bar.classList.toggle('danger', remaining <= 5);
        }

        if (this.onTick) this.onTick(remaining);

        if (remaining <= 0) {
            this.stop();
            this.active = false;
            if (this.onExpire) this.onExpire();
        }
    }
}