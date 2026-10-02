# Déploiement du site public

    firebase deploy --only hosting --project ablony-a5db9

Le site part sur **https://ablony-site.web.app**.

## Ce qui n'est pas téléversé, et pourquoi

Les exclusions de `firebase.json` ne sont pas du confort :

| Exclu | Raison |
|---|---|
| `.env` | identifiants SMTP — ils n'ont rien à faire sur un serveur public |
| `data/**` | les inscriptions à la liste d'attente, donc des adresses e-mail de vraies personnes |
| `*.php` | **Firebase Hosting ne sert que du statique** : un `.php` téléversé serait renvoyé en texte brut, exposant la logique d'envoi de mail et tout ce qu'elle contient |
| `docker/**`, `Dockerfile` | sources de construction, sans usage côté navigateur |
| `README.md`, `DEPLOIEMENT.md` | notes internes, sans usage pour un visiteur |

> ⚠️ **Les `.md` des pages légales, eux, doivent être publiés.** Les pages HTML
> ne contiennent pas le texte : elles le chargent à l'exécution
> (`fetch('mention_legal.md')`) et le mettent en forme. Les exclure rendait les
> quatre pages vides, avec « Erreur lors du chargement » — alors que le HTML
> répondait bien 200. Vérifier le code de retour d'une page ne dit rien de ce
> qu'elle affiche.

## Conséquence à connaître : les formulaires

Trois appels de `main.js` visent des scripts PHP :

- `submit_waitlist.php` (inscription à la liste d'attente, deux endroits)
- `submit_form.php` (formulaire de contact)

**Ces deux formulaires ne fonctionnent pas sur Firebase Hosting**, qui n'exécute pas PHP. Les pages s'affichent normalement, mais l'envoi échoue.

Ce qui fonctionne sans réserve : les quatre pages légales et d'aide (CGU, politique de
confidentialité, mentions légales, aide), entièrement statiques — et ce sont elles que
l'application ouvre et que Google Play vérifie.

### Les trois façons d'en sortir

1. **Laisser les formulaires chez un hébergeur PHP** (InfinityFree aujourd'hui) et ne
   servir ici que les pages de contenu. C'est l'état actuel.
2. **Porter les deux scripts en Cloud Functions.** Le projet en a déjà : il s'agirait de
   deux fonctions HTTP qui écrivent dans Firestore et envoient le mail. C'est la solution
   durable — un seul hébergement, pas de PHP, et la liste d'attente rejoint les données
   du reste du produit.
3. **Renvoyer les formulaires vers un service tiers** (Formspree, Tally…), le temps du
   lancement.
