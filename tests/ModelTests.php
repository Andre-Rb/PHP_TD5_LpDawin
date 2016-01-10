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


        // There is one book
        $books = $this->app['model']->getBooks();
        $this->assertEquals(1, count($books));


    }
}
