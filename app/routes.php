<?php

// Namespaces
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// HOME
$app
    ->get(
        '/',
        function(Request $request, Response $response)
        {

            $query = $this->db->query('SELECT * FROM pokemons LIMIT 0,25');
            $pokemons = $query->fetchAll();

            // Data view
            $dataView = [
                'pokemons' => $pokemons,
            ];

            // Render
            return $this->view->render($response, 'pages/home.twig', $dataView);
        }
    )
    ->setName('home');

// SEARCH
$app
    ->get(
        '/pokemon/search',
        function(Request $request, Response $response, $arguments)
        {
            $url = $this->router->pathFor('pokemon', [ 'slug' => $_GET['search'] ]);

            return $response->withRedirect($url);
        }
    )
    ->setName('search');

// POKEMONS LIST
$app
    ->get(
        '/page/{pageNumber:\d+}',
        function(Request $request, Response $response, $arguments)
        {
            $query = $this->db->query('SELECT COUNT(*) AS count FROM pokemons');
            $total = $query->fetch();

            $pokemonsPerPage=25;

            $numberOfPages = ceil( $total->count/$pokemonsPerPage );

            $actualPage = $arguments['pageNumber'];

            $offset=($actualPage-1)*25;

            $prepare = $this->db->prepare('SELECT * FROM pokemons LIMIT 25 OFFSET :offset');
            $prepare->bindValue(':offset', $offset, PDO::PARAM_INT);
            $prepare->execute();
            $pokemons = $prepare->fetchAll();

            // Data view
            $dataView = [
                'numberOfPages' => $numberOfPages,
                'pokemons' => $pokemons,
            ];

            // Render
            return $this->view->render($response, 'pages/pokemons.twig', $dataView );
        }
    )
    ->setName('pokemons');





// TYPES
$app
    ->get(
        '/types',
        function(Request $request, Response $response)
        {
            $query = $this->db->query('SELECT * FROM types');
            $types = $query->fetchAll();

            $dataView = [
                'types' => $types,
            ];

            return $this->view->render($response, 'pages/types.twig', $dataView);
        }
    )
    ->setName('types');





// TYPE
$app
    ->get(
        '/types/{slug:[a-zA-Z0-9_-]+}/{pageNumber:\d+}',
        function(Request $request, Response $response, $arguments)
        {

            $prepare = $this->db->prepare('SELECT * FROM types WHERE slug = :slug');
            $prepare->bindValue('slug', $arguments['slug']);
            $prepare->execute();
            $type = $prepare->fetch();

            if(!$type)
            {
                throw new \Slim\Exception\NotFoundException($request, $response);
            } 

            $prepare = $this->db->prepare('SELECT COUNT(*) AS count FROM pokemons LEFT JOIN pokemons_types ON pokemons.id = pokemons_types.id_pokemon WHERE pokemons_types.id_type = :type_id');
            $prepare->bindValue(':type_id', $type->id);
            $prepare->execute();
            $total = $prepare->fetch();

            $pokemonsPerPage=25;

            $numberOfPages = ceil( $total->count/$pokemonsPerPage );

            $actualPage = $arguments['pageNumber'];

            $offset=($actualPage-1)*25;

            $prepare = $this->db->prepare('SELECT * FROM pokemons LEFT JOIN pokemons_types ON pokemons.id = pokemons_types.id_pokemon WHERE pokemons_types.id_type = :type_id LIMIT 25 OFFSET :offset');
            $prepare->bindValue(':type_id', $type->id);
            $prepare->bindValue(':offset', $offset, PDO::PARAM_INT);
            $prepare->execute();
            $pokemons = $prepare->fetchAll();

            $dataView = [
                'type' => $type,
                'pokemons' => $pokemons,
                'numberOfPages' => $numberOfPages,
            ];

            // Render
            return $this->view->render($response, 'pages/type.twig', $dataView);
        }
    )
    ->setName('type');

// POKEMON
$app
    ->get(
        '/{slug:[a-zA-Z0-9_-]+}',
        function(Request $request, Response $response, $arguments)
        {
            $prepare = $this->db->prepare('SELECT * FROM pokemons WHERE slug = :slug');
            $prepare->bindValue('slug', $arguments['slug']);
            $prepare->execute();
            $pokemon = $prepare->fetch();

            if(!$pokemon)
            {
                throw new \Slim\Exception\NotFoundException($request, $response);
            } 

            $prepare = $this->db->prepare('SELECT * FROM types INNER JOIN pokemons_types ON types.id = pokemons_types.id_type WHERE pokemons_types.id_pokemon = :pokeid');
            $prepare->bindValue(':pokeid', $pokemon->id);
            $prepare->execute();
            $type = $prepare->fetch();  

            $dataView = [
                'pokemon' => $pokemon,
                'type' => $type,
            ];
            // Render
            return $this->view->render($response, 'pages/pokemon.twig', $dataView);
        }
    )
    ->setName('pokemon');

// RANDOM POKEMON
$app
    ->get(
        '/pokemon/random',
        function(Request $request, Response $response, $arguments)
        {
            $query = $this->db->query('SELECT slug FROM pokemons ORDER BY RAND() LIMIT 1');
            $pokemon = $query->fetch();

            $url = $this->router->pathFor('pokemon', [ 'slug' => $pokemon->slug ]);

            return $response->withRedirect($url);
        }
    )
    ->setName('random');