Pour lancer le projet il faut:
  - Ouvrir phpMyAdmin et créer une nouvelle database avec le nom: "movynov"
  - Dans le terminal du projet un petit "composer update" pour tout importer
  - Créer : faire la migration:
      - php bin/console make:migration
      - php bin/console doctrine:migrations:migrate
          - Il vous demandera si vous êtes sur et vous répondres "yes" ou appuyerez sur "Entré"
  - Si tout c'est bien passé un petit "symfony serve" permettra de lancer le serveur

De base il y a 2 utilisateur qui sont dans la base de donnée:
  - L'administrateur à pour email: admin@gmail.com et mot de passe: password
  - L'utilisateur classique qui à pour email: utilisateur@gmail.com et mot de passe: password


/!\ Il faut bien faire attention à enlever "ext:sodium" dans le php.ini
