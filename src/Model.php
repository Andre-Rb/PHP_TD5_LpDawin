<?php

class Model
{
    protected $pdo;

    public function __construct(array $config)
    {
        try {
            if ($config['engine'] == 'mysql') {
                $this->pdo = new \PDO(
                    'mysql:dbname=' . $config['database'] . ';host=' . $config['host'],
                    $config['user'],
                    $config['password']
                );
                $this->pdo->exec('SET CHARSET UTF8');
            } else {
                $this->pdo = new \PDO(
                    'sqlite:' . $config['file']
                );
            }
        } catch (\PDOException $error) {
            throw new ModelException('Unable to connect to database');
        }
    }

    /**
     * Tries to execute a statement, throw an explicit exception on failure
     */
    protected function execute(\PDOStatement $query, array $variables = array())
    {
        if (!$query->execute($variables)) {
            $errors = $query->errorInfo();
            throw new ModelException($errors[2]);
        }
        return $query;
    }

    /**
     * Inserting a book in the database
     * Returns the numbers of copies inserted
     */
    public function insertBook($title, $author, $synopsis, $image, $copies)
    {
        $query = $this->pdo->prepare('INSERT INTO livres (titre, auteur, synopsis, image)
            VALUES (?, ?, ?, ?)');

        $this->execute($query, array($title, $author, $synopsis, $image));

        $lastInsertedId = $this->getOneBookWithParams($title, $author)['id'];
        $totalInsert = 0;

        for ($i = 0; $i < $copies; $i++) {
            $query = $this->pdo->prepare('INSERT INTO exemplaires (book_id) VALUES (?)');

            $this->execute($query, array($lastInsertedId));
            $totalInsert++;
        }

        return $totalInsert;
    }

    /**
     * Getting all the books
     */
    public function getBooks()
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres');

        $this->execute($query);

        return $query->fetchAll();
    }

    /**
     * Getting one particular book and all his info except borrowing status
     */
    public function getOneBook($id)
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres WHERE id=?');

        $this->execute($query, array($id));

        return $query->fetchAll()[0];
    }

    /**
     * Getting all copies of a book that are have been borrowed and are still borrowed
     * @param $bookId
     * @param $stillBorrowed
     * @return array
     * @throws ModelException
     */
    public function getBorrowedCopiesOfThatBook($bookId, $stillBorrowed)
    {
        $query = $this->pdo->prepare('
            SELECT
              library.exemplaires.id as \'copyId\',
              library.livres.id as \'bookIdn\',
              library.emprunts.fini as \'borrowingDone\'
            FROM library.livres
              INNER JOIN library.exemplaires
                ON library.exemplaires.book_id = library.livres.id
              INNER JOIN library.emprunts
                ON library.emprunts.exemplaire = library.exemplaires.id
            WHERE library.livres.id = ?
            AND library.emprunts.fini = ?'
        );

        $this->execute($query, array($bookId, $stillBorrowed));

        $queryRes = $query->fetchAll();
        $res = [];
        if (!empty($queryRes)) {
            foreach ($queryRes as $item) {
                $res[] = $item['copyId'];
            }
        }
        return $res;
    }

    /**
     * Getting one particular book
     * @param $titre titre of the book we're looking for
     * @param $auteur auteur of the book we're looking for
     * @return an array of the book
     * @throws ModelException
     */
    public function getOneBookWithParams($titre, $auteur)
    {
        $query = $this->pdo->prepare('
            SELECT livres.* FROM livres
            WHERE titre=?
            AND auteur=?
        ');

        $this->execute($query, array($titre, $auteur));

        return $query->fetchAll()[0];
    }


    /**
     * Get all copies of a book
     */
    public function getAllCopiesOfABookById($id)
    {


        $query = $this->pdo->prepare('
            SELECT exemplaires.* FROM exemplaires
           WHERE exemplaires.book_id=?
        ');

        $this->execute($query, array($id));

        return $query->fetchAll();
    }


    /**
     * Inserting a new borrowing in the database
     */
    public function insertBorrowing($exemplaire, $personName, $bgnDate, $endDate)
    {

        $query = $this->pdo->prepare('INSERT INTO emprunts (personne, exemplaire, debut, fin, fini)
            VALUES (?, ?, ?, ?,?)');

        $this->execute($query, array($personName, $exemplaire, $bgnDate, $endDate, 0));
        return true;

    }

    /**
     * @param $copyId id of the copy what should be returned
     * @return bool if the book as been returned has queried
     * @throws ModelException
     */
    public function returnACopy($copyId)
    {

        $query = $this->pdo->prepare('
                    UPDATE library.emprunts
                    SET library.emprunts.fini = 1
                    WHERE library.emprunts.exemplaire = ?
            ');

        $this->execute($query, array($copyId));

        return true;

    }


}
