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