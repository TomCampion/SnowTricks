<h1>Snowtricks</h1>

SnowTricks is a collaborative website to introduce snowboarding to the general public and help them learn tricks.

## Installation
1 - Clone or download the GitHub repository in the desired folder:
```
    git clone https://github.com/TomCampion/SnowTricks
```
2 - Configure your environment variables in the .env file

3 - Create the database if it does not already exist, type the command below :
```
    php bin/console doctrine:database:create
```
4 - Create the different tables in the database by applying the migrations :
```
    php bin/console doctrine:migrations:migrate
```
5 - (Optional) If u want to install the fixtures to have a dummy data demo, create a new user using the registration page then run this command :
```
    php bin/console doctrine:fixtures:load --append
```
