<?php

class ModelTests extends BaseTests
{
    /**
     * Testing insertion of a book
     */
    public function testBookInsert()
    {
        // There is no book
        $books = $this->app['model']->getBooks();
        $this->assertEquals(0, count($books));

        // Inserting one and checking if there is enough copies
        $numberOfCopiesWanted = 3;
        $numberOfCopiesInserted = $this->app['model']->insertBook('Test', 'Someone', 'A test book', 'image', $numberOfCopiesWanted);
        $this->assertEquals($numberOfCopiesWanted, $numberOfCopiesInserted);


        // We get the id of a book and check if we got the right one
        $bookID = $this->app['model']->getOneBookWithParams('Test', 'Someone')['id'];
        $bookTitle = $this->app['model']->getOneBookWithParams('Test', 'Someone')['titre'];

        $this->assertEquals("Test", $bookTitle);
        $this->assertEquals(1, $bookID);


        // There is one book
        $books = $this->app['model']->getBooks();
        $this->assertEquals(1, count($books));

        $copiesOfABook = $this->app['model']->getAllCopiesOfABookById($bookID);
        $this->assertEquals($numberOfCopiesWanted, count($copiesOfABook));

//        Checking if the copies got the right book id
        $oneCopy = $copiesOfABook[0];
        $this->assertEquals($bookID, $oneCopy['book_id']);

        $insertSuccessfull = $this->app['model']->insertBorrowing( $oneCopy['id'], "André", "2016-01-01", "2017-01-01");
        $this->assertTrue($insertSuccessfull);


    }
}
