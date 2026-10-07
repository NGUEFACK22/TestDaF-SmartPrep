# Guide d'utilisation — SYNPHONIE (100 % QCM écrit, par niveau)

## 1. Candidat

### Parcours global

```
/ (Accueil) → /register (inscription) → /login → /dashboard
→ /preparation (niveaux A1 → C2 : banque QCM ou session IA)
→ /exam/{attempt} (session chronométrée)
→ /results/{attempt} → /results/{attempt}/report (rapport détaillé)
```

### Tableau de bord (`/dashboard`)

Niveau Lesen, tentative en cours (reprise), tests commencés/terminés, score moyen,
exercices recommandés, bandeau Espace Élite (si débloqué).

### Entraînement par niveau (`/preparation`)

Cartes A1 → C2. Chaque niveau propose :
- **Banque QCM** (A1–C1) : session chronométrée, questions **tirées sans remise**
  (différentes à chaque session, calibrées sur le cadre du niveau).
- **Session IA inédite** (C1–C2) : minimum 20 QCM générés à la demande, calibrés
  sur vos faiblesses, minuteur par question selon la difficulté (B2 90 s, C1 120 s, C1+ 150 s).
- Le C1 suit le **format TestDaF** (QCM + Lückentext + vrai/faux), écrit uniquement.

Dernier score affiché par niveau, avec lien vers le détail.

### Session d'examen (mode examen)

1. Carte niveau → **S'entraîner** (banque) ou **Générer une session IA** → tentative
   créée côté serveur → `/exam/{attempt}`.
2. Interface : en-tête (`SYNPHONIE / Lesen / Aufgabe X`), **chronomètre** de la tâche,
   barre de progression globale, consigne, texte, Fragen numérotées (`Frage 1 / N`
   + pastille `· En attente` / `✓ Répondu`), boutons **SUIVANT** / **WEITER**.

Règles du moteur d'examen (le serveur est l'autorité) :

- Chaque **Aufgabe** a sa propre durée (en base). Le compte à rebours s'affiche ; à **30 s** reste, alerte visuelle.
- Cliquer SUIVANT/WEITER ouvre une **fenêtre de confirmation** (temps restant + état des réponses). Oui = on avance, Non = on reste.
- **Si vous validez avant la fin** : réponses sauvegardées, tâche verrouillée définitivement, le temps restant est perdu, tâche suivante démarrée immédiatement.
- **Si le temps arrive à 00:00** : « Die Bearbeitungszeit ist abgelaufen. » — réponses sauvegardées, tâche verrouillée, passage automatique à la suivante. Les champs se grisent.
- **Retour en arrière impossible** (le serveur refuse tout accès à une tâche antérieure, expirée ou future).
- **Réseau coupé / onglet fermé** : l'état est côté serveur. En reprenant, le serveur recalcule le temps restant (ou verrouille et passe à la suite si expiré).
- Les réponses sont **autosauvegardées** (toutes les 10 s + à chaque saisie) — refusées par le serveur si la tâche est expirée, avec message explicite.

### Résultats et rapport

- `/results/{attempt}` : score, pourcentage, note /20 et TDN estimé, score par partie,
  points à améliorer, correction détaillée avec **explication pédagogique**
  (« Votre réponse : B — Bonne réponse : C — Pourquoi : … »).
- `/results/{attempt}/report` : résumé global, erreurs fréquentes, évolution, temps utilisé.
- `/results/{attempt}/pdf` : version imprimable (export PDF via le navigateur).
- Les scores sont des **évaluations pédagogiques indicatives**, pas des notes officielles TestDaF.

### Espace Élite — Défi IA (`/challenges`)

Deux scores parfaits (100 %) d'affilée sur un même niveau débloquent des défis IA
supplémentaires (min 20 QCM inédits, calibrés sur vos erreurs). Notification +
bandeau dashboard. Génération en file d'attente (actualisation auto).

### Notifications

`/notifications` : Espace Élite débloqué, défi IA prêt, message administrateur.

## 2. Administrateur

Accès : `/admin` (rôle `admin` requis — middleware `role:admin`).

### Dashboard (`/admin`)

Utilisateurs, tests de niveau publiés, exercices QCM, tentatives en cours,
Espaces Élite débloqués, défis IA (prêts / échoués), score moyen, activité récente.

### Gestion des exercices (`/admin/exercises`)

Bibliothèque d'exercices QCM réutilisables : titre, type (famille TestDaF Lesen),
niveau (A1–C2), consigne, durée, points, difficulté, texte, questions, options,
bonne réponse, explication, statut de publication. 100 % texte — aucun média.

### Utilisateurs (`/admin/users`)

Liste des comptes, rôles (candidat / admin), statut (actif / bloqué). Modifier les rôles/statuts — **changer impérativement les mots de passe démo en production**.

### Paramètres IA (`/admin/settings`)

`ai_enabled`, `ai_provider`, `ai_model`, `max_ai_requests` (quota journalier de
générations, défaut 100). En échec API, la génération passe en « échouée » avec
message, rejouable. Les secrets restent dans `.env`, jamais exposés au navigateur.

## 3. Sécurité (points à retenir)

- **Jamais de confiance au navigateur** : identité, rôles, tentatives, temps, états, réponses, scores et IA sont validés côté serveur (Laravel).
- Toute réponse envoyée est re-checkée : utilisateur possesseur de la tentative, question de la forme active et courante, non expirée, non verrouillée.
- Rate limiting sur les routes d'envoi (réponses 180/min, IA 5/min, login 5/min).
- Les clés API (Gemini) ne sont jamais exposées au navigateur.
- Commandes RGPD : `synphonie:purge` (vieux logs), export + suppression de compte côté candidat.
