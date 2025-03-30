# Plugin de recherche de tentatives de quiz pour Moodle

Ce plugin ajoute un web service à Moodle permettant de rechercher les tentatives de quiz selon différents critères.

## Fonctionnalités

- Recherche des tentatives de quiz par ID de quiz
- Filtrage par période (timestamps de début et de fin)
- Filtrage par utilisateur
- Retourne les informations détaillées sur les tentatives et les utilisateurs

## Installation

1. Téléchargez le plugin
2. Décompressez-le dans le dossier `local/ws_quiz_search_attempt` de votre installation Moodle
3. Connectez-vous à votre site Moodle en tant qu'administrateur
4. Allez dans Administration du site > Notifications
5. Installez le plugin

## Configuration

1. Allez dans Administration du site > Plugins > Services Web > Services externes
2. Activez les services Web si ce n'est pas déjà fait
3. Activez le protocole REST si ce n'est pas déjà fait
4. Dans la section "Services Web", activez le service "Recherche de tentatives de quiz"

## Utilisation

### Endpoint

```
POST /webservice/rest/server.php
```

### Paramètres

- `wsfunction`: `local_ws_quiz_search_attempt`
- `moodlewsrestformat`: `json`
- `key`: Tableau des clés des paramètres (obligatoire)
  - Valeurs possibles : quizid, userid, before_timestamp, after_timestamp
- `value`: Tableau des valeurs des paramètres (obligatoire)
  - Les valeurs doivent correspondre aux clés dans le même ordre

### Exemple de requête

```bash
curl -X POST "https://votre-site-moodle.com/webservice/rest/server.php" \
     -d "wsfunction=local_ws_quiz_search_attempt" \
     -d "moodlewsrestformat=json" \
     -d "key[0]=quizid" \
     -d "value[0]=1" \
     -d "key[1]=before_timestamp" \
     -d "value[1]=1693344180" \
     -d "key[2]=after_timestamp" \
     -d "value[2]=1693261380" \
     -d "wstoken=votre_token"
```

### Réponse

```json
[
    {
        "attemptid": 1,
        "userid": 2,
        "user": {
            "firstname": "John",
            "lastname": "Doe",
            "email": "john.doe@example.com"
        },
        "attempt": {
            "number": 1,
            "uniqueid": 1,
            "state": "finished",
            "sumgrades": 8.5,
            "layout": "1,2,3,0",
            "currentpage": 0
        },
        "timing": {
            "timestart": 1693261380,
            "timefinish": 1693344180
        }
    }
]
```

## Permissions requises

- `mod/quiz:viewreports` : Pour accéder aux rapports du quiz

## Support

Pour toute question ou problème, veuillez créer une issue dans le dépôt GitHub du plugin. 