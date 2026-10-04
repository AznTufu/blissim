# Blissim

Catalogue de produits récupérés depuis [fakestoreapi.com](https://fakestoreapi.com/products), avec ajout, modification et suppression de commentaires sur chaque fiche produit.

Le fichier `blissim.sql` contient la partie SQL (modèle e-commerce, jeu de données et requêtes demandées).

## Prérequis

**Avec Docker (recommandé)**
- Docker Desktop (ou Docker Engine + Compose)

**Sans Docker**
- PHP 8.4+
- Composer
- MySQL 8.0+

## Installation avec Docker

1. Cloner le dépôt
````shell
$ git clone https://github.com/AznTufu/blissim.git
````
2. Aller dans le dossier du projet
````shell
$ cd blissim
````
3. Lancer les conteneurs
````shell
$ docker compose up -d --build
````
Au premier lancement, le conteneur installe les dépendances Composer, MySQL importe `blissim.sql`, puis les migrations créent la table des commentaires. Pour suivre l'avancement :
````shell
$ docker compose logs -f app
````
4. Aller sur http://localhost:8000

### Identifiants de la base (Docker)

| Hôte (depuis la machine) | `127.0.0.1` |
|---|---|
| Port | `3307` |
| Base | `blissim` |
| Utilisateur | `app` / `app`|

### Commandes utiles

````shell
$ docker compose exec app bash
$ docker compose exec app php bin/phpunit
$ docker compose down
````

## Installation sans Docker

1. Cloner le dépôt et aller dans le dossier
````shell
$ git clone https://github.com/AznTufu/blissim.git
$ cd blissim
````
2. Installer les dépendances
````shell
$ composer install
````
3. Créer un fichier `.env.local` et y renseigner la connexion MySQL :
```dotenv
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/blissim?serverVersion=8.0&charset=utf8mb4"
```
4. Créer la base
````shell
$ php bin/console doctrine:database:create
````
5. Lancer les migrations (table `comment`)
````shell
$ php bin/console doctrine:migrations:migrate
````
6. Importer la partie SQL (tables e-commerce et données de test), en remplaçant `db_user` par votre utilisateur MySQL. Le mot de passe est demandé ensuite.
````shell
$ mysql -u db_user -p blissim -e "source blissim.sql"
````
Ou importer `blissim.sql` dans la base `blissim` depuis un client graphique (phpMyAdmin, TablePlus…).
7. Lancer le serveur
````shell
$ symfony serve
````
8. Aller sur http://localhost:8000

## Tests

````shell
$ php bin/phpunit
````

## Choix techniques

- **Symfony 8.1** : contrôleurs (`src/Controller`), service d'accès à l'API (`src/Service`), entité et repository (`src/Entity`, `src/Repository`), templates Twig (`templates/`).
- **Commentaires via PDO** : `CommentRepository` enregistre et supprime les commentaires avec des requêtes SQL préparées sur la connexion PDO. La lecture passe par Doctrine, et Doctrine Migrations crée la table.
- **Erreurs de l'API** : timeout de 3 s et une nouvelle tentative. Si l'API reste indisponible, l'application bascule sur un catalogue local (`data/products.json`) et n'appelle plus l'API pendant quelques minutes, pour ne pas ralentir chaque page. Si le catalogue local est lui aussi illisible, une page 503 s'affiche.
- **Erreurs de la base** : une base injoignable ou une erreur SQL affiche une page 503 claire au lieu d'une erreur 500 (`src/EventListener/ServiceUnavailableListener.php`).
- **Sécurité des formulaires** : protection CSRF sur l'ajout, la modification et la suppression, validation côté serveur (commentaire non vide, 1000 caractères maximum), échappement automatique par Twig.
- **Tables de `blissim.sql`** : elles sont exclues du suivi Doctrine (`schema_filter` dans `config/packages/doctrine.yaml`), pour que les migrations ne les modifient jamais.
