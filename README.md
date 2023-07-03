Pour lancer le projet il faut:
  - Ouvrir phpMyAdmin et créer une nouvelle database avec le nom: "movynov"
  - Dans le terminal du projet un petit "composer update" pour tout importer
  - Créer : faire la migration:
      - php bin/console make:migration
      - php bin/console doctrine:migrations:migrate
  - Si tout c'est bien passé un petit "symfony serve" permettra de lancer le serveur
