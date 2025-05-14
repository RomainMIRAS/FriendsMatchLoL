# FriendsMatchLoL

A web application that lets you track your friends' ongoing League of Legends matches using the Riot Games API.

## Features

- Track your friends' current matches
- Receive notifications when your friends start a game
- View details of ongoing matches
- See your friends' match history
- Modern and responsive user interface

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- PHP PDO extension
- Web server (Apache or Nginx)
- A valid Riot Games API key

## Installation

1. Clone this repository into your web directory:
    ```
    git clone https://github.com/your-username/FriendsMatchLoL.git
    ```

2. Set up your MySQL database and edit the `config/config.php` file with your settings:
    ```php
    define('DB_HOST', 'localhost');
    define('DB_USER', 'your_user');
    define('DB_PASS', 'your_password');
    define('DB_NAME', 'friendsmatchlol');
    ```

3. Set your Riot Games API key in the same file:
    ```php
    define('RIOT_API_KEY', 'RGAPI-YOUR-API-KEY');
    ```

4. Run the installation script to create the database tables:
    ```
    php install.php
    ```

5. Make sure the folder permissions are set correctly:
    ```
    chmod -R 755 /path/to/FriendsMatchLoL
    chmod -R 777 /path/to/FriendsMatchLoL/cache
    chmod -R 777 /path/to/FriendsMatchLoL/logs
    ```

6. Access the application via your web browser:
    ```
    http://your-server/FriendsMatchLoL/
    ```

## Usage

1. Sign up or log in to the application
2. Add your friends by entering their summoner name and region
3. Check the homepage to see your friends' game statuses
4. Receive notifications when your friends start a game
5. Click "Match Details" to see more information about the ongoing match

## Limitations

- Friends must be added manually
- Riot API rate limits may affect performance
- Updates occur every 60 seconds to comply with API limits

## Contribution

Contributions are welcome! Feel free to open an issue or submit a pull request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Disclaimer

FriendsMatchLoL is not endorsed by Riot Games and does not reflect the views or opinions of Riot Games or anyone involved in producing or managing Riot Games properties.