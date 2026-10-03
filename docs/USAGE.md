# Guide d'utilisation

## 1. Candidat

### Parcours global

```
/ (Accueil) → /register (inscription) → /login → /dashboard
→ /preparation/{lesen|hoeren|schreiben|sprechen} (entraînement libre)
→ /modelltests (liste des 10 tests) → /exam/{attempt} (passage)
→ /results/{attempt} → /results/{attempt}/report (rapport détaillé)
```

### Tableau de bord (`/dashboard`)

Progression par compétence (Lesen / Hören / Schreiben / Sprechen), dernier test, tests commencés/terminés, exercices recommandés et activités récentes.

### Espaces de préparation (`/preparation/{competence}`)

Cours/méthodes/conseils + exercices classés par difficulté (A2→C1). Chaque exercice s'ouvre en **mode entraînement** (`/training/exercises/{id}`) : sans chronomètre, avec indices, correction immédiate et possibilité de recommencer.

### Passer un Modelltest (mode examen)

1. `/modelltests` → choisir **Modelltest N** → bouton **Commencer le Modelltest** (confirmation : « ce mode reproduit les conditions d'un examen numérique »).
2. La tentative est créée côté serveur ; le parcours s'enchaîne : **Lesen → Hören → Schreiben → Sprechen**.
3. Interface d'examen : en-tête (`TESTDAF / Lesen / Aufgabe 3 von 7`), **chronomètre** de la tâche, barre de progression, consigne, contenu (texte/audio/vidéo/sujet), zone de réponses, bouton **Weiter**.

Règles du moteur d'examen (le serveur est l'autorité) :

- Chaque **Aufgabe** a sa propre durée (en base). Le compte à rebours s'affiche ; à **30 s** reste, alerte visuelle.
- **Si vous validez avant la fin** : réponses sauvegardées, tâche verrouillée définitivement, le temps restant est perdu, tâche suivante démarrée immédiatement.
- **Si le temps arrive à 00:00** : « Die Bearbeitungszeit ist abgelaufen. » — réponses sauvegardées, tâche verrouillée, passage automatique à la suivante.
- **Retour en arrière impossible** (bouton précédent désactivé ; le serveur refuse tout accès à une tâche antérieure, expirée ou future).
- **Réseau coupé / onglet fermé** : l'état est côté serveur. En reprenant, le serveur recalcule le temps restant (ou verrouille et passe à la suite si expiré).
- Les réponses texte (Lesen, Kurzantwort) sont **autosauvegardées toutes les 10 s** — refusées par le serveur si la tâche est expirée.

### Hören

Le **Hörtext** est joué dans un **lecteur audio** (audio synthétique de la transcription originale, voix allemande `de-DE`) ; le transkript est affiché sous le lecteur. L'audio est servi depuis le **stockage privé** via `/media/{id}` (session requise, jamais de URL publique directe).

### Schreiben

Éditeur de texte avec **compteur de mots**, affichage du sujet + texte source + **graphique** (bar chart Chart.js des données de la « Grafik »), autosave silencieux, fin automatique et **verrouillage du champ** à l'échéance.

### Sprechen

Le navigateur demande l'autorisation du **microphone** (Page non sécurisée → autoriser ; ne fonctionne que sur HTTPS ou `127.0.0.1`). Séquence : consigne (audio/vidéo possible) → **temps de préparation** → enregistrement (barre de temps restant) → arrêt automatique à l'échéance. Avant validation, vous pouvez **réécouter** et **refaire** l'enregistrement ; après validation, il est définitif. Le fichier (WebM) est envoyé vers le stockage privé du serveur.

> Formats acceptés (Sprechen) : `audio/webm`, `ogg`, `mp3`, `wav`, `m4a` — max **20 Mo**. Vidéo : `webm`, `mp4`, `ogv`, `mov` — max **100 Mo**.

### Résultats et rapport

- `/results/{attempt}` : par section — points, pourcentage, bonnes/mauvaises réponses avec **explication pédagogique** (« Votre réponse : B — Bonne réponse : C — Pourquoi : … — Conseil : … »), commentaires IA et correcteur, statut des analyses (« Analyse en cours… » → disponible).
- `/results/{attempt}/report` : résumé global, erreurs fréquentes, points forts/à améliorer, recommandations, évolution, temps utilisé.
- Les scores IA sont des **évaluations pédagogiques indicatives**, pas des notes officielles TestDaF.

### Notifications

`/notifications` : nouveau Modelltest disponible, correction disponible, analyse IA terminée, message administrateur.

## 2. Administrateur

Accès : `/admin` (rôle `admin` requis — middleware `role:admin`).

### Dashboard (`/admin`)

Utilisateurs, tentatives, tests terminés, exercices réalisés, score moyen par compétence, demandes de correction, analyses IA (ok / erreurs), stockage média, activité récente.

### Gestion des Modelltests (`/admin/modelltests`)

- **CRUD** complet : titre, thème, description, ordre, statut (brouillon / publié).
- Ajout des 4 sections (Lesen, Hören, Schreiben, Sprechen) avec leur **ordre** et leurs **Aufgaben** (exercices de la bibliothèque), chaque tâche avec sa **durée** (`duration_seconds`).
- Ajout des **questions**, **options**, **bonne réponse**, **points**, **explication**.
- **Publication** : le système contrôle la complétude (toutes les tâches présentes, questions valides, bonnes réponses définies, durées, solutions) avant de passer au statut « publié ».

### Gestion des exercices (`/admin/exercises`)

Bibliothèque d'exercices réutilisables : titre, section, type (famille TestDaF), niveau (A2–C1), consigne, durée, points, difficulté, texte, médias (audio/vidéo/image/graphique en upload privé), statut de publication.

### Corrections manuelles (`/admin/corrections`)

Liste des productions **Schreiben** et **Sprechen** à corriger. Pour chacune : consultation de la consigne, lecture de la réponse / **écoute audio** / visionnage vidéo, **attribution des points** (grille 0–20 par critère), **commentaire**, validation. Le candidat est notifié.

### Utilisateurs (`/admin/users`)

Liste des comptes, rôles (candidat / admin), statut (actif / bloqué). Modifier les rôles/statuts — **changer impérativement les mots de passe démo en production**.

### Paramètres IA (`/admin/settings`)

`ai_enabled`, `ai_provider`, `ai_model`, `max_ai_requests`, `ai_auto_evaluation`, `ai_manual_review` (miroir des variables `.env` correspondantes). Les quotas s'appliquent ; en échec API, l'analyse passe en « en attente » sans perte de la réponse et reste rejouable.

## 3. Sécurité (points à retenir)

- **Jamais de confiance au navigateur** : identité, rôles, tentatives, temps, états, réponses, scores, fichiers et IA sont validés côté serveur (Laravel).
- Toute réponse envoyée est re-checkée : utilisateur possesseur de la tentative, tâche autorisée (ordre), non expirée, non verrouillée.
- Les médias candidats sont en **stockage privé** (`storage/app/private/candidate_audio|video/...`) et servis par streaming contrôlé — jamais de URL publique directe, jamais d'exécution de fichier uploadé.
- Rate limiting sur les routes d'envoi (réponses 180/min, audio 30/min, IA 20/min).
- Les clés API (Gemini / Whisper / Speechace) ne sont jamais exposées au navigateur.
