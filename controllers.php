<?php
use Gregwar\Image\Image;


$app->match('/', function () use ($app) {
    return $app['twig']->render('home.html.twig');
})->bind('home');


$app->match('/viewBook/{bookId}', function ($bookId) use ($app) {
    return $app['twig']->render('bookDetails.html.twig', array(
        'lookedUpBook' => $app['model']->getOneBook($bookId),
        'copiesOfBook' => $app['model']->getAllCopiesOfABookById($bookId),
        'borrowedCopiesOfBook' => $app['model']->getBorrowedCopiesOfThatBook($bookId, 0)
    ));
})->bind('viewBook');


$app->match('/books', function () use ($app) {
    return $app['twig']->render('books.html.twig', array(
        'books' => $app['model']->getBooks()
    ));
})->bind('books')->method("get");


//LOGIN

$app->match('/admin', function () use ($app) {
    $request = $app['request'];
    $success = false;
    if ($request->getMethod() == 'POST') {
        $post = $request->request;
        if ($post->has('login') && $post->has('password') &&
            isset($app['config']['admins'][$post->get('login')]) &&
            $app['config']['admins'][$post->get('login')] == $post->get('password')
        ) {
            $app['session']->set('admin', true);
            $success = true;
        }
    }
    return $app['twig']->render('admin.html.twig', array(
        'success' => $success
    ));
})->bind('admin');


$app->match('/logout', function () use ($app) {
    $app['session']->remove('admin');
    return $app->redirect($app['url_generator']->generate('admin'));
})->bind('logout');

$app->match('/addBook', function () use ($app) {
    if (!$app['session']->has('admin')) {
        return $app['twig']->render('shouldBeAdmin.html.twig');
    }

    $request = $app['request'];
    if ($request->getMethod() == 'POST') {
        $post = $request->request;
        if ($post->has('title') && $post->has('author') && $post->has('synopsis') &&
            $post->has('copies')
        ) {
            $files = $request->files;
            $image = '';

            // Resizing image
            if ($files->has('image') && $files->get('image')) {
                $image = sha1(mt_rand() . time());
                Image::open($files->get('image')->getPathName())
                    ->resize(240, 300)
                    ->save('uploads/' . $image . '.jpg');
                Image::open($files->get('image')->getPathName())
                    ->resize(120, 150)
                    ->save('uploads/' . $image . '_small.jpg');
            }

            // Saving the book to database
            $app['model']->insertBook($post->get('title'), $post->get('author'), $post->get('synopsis'),
                $image, (int)$post->get('copies'));
        }
    }

    return $app['twig']->render('addBook.html.twig');
})->bind('addBook');


$app->get('/borrow/{bookId}/{copyId}', function ($bookId, $copyId) use ($app) {
    $request = $app['request'];
    if ($request->getMethod() == 'GET') {
        return $app['twig']->render('borrowForm.html.twig', array(
            'bookId' => $bookId,
            'copyId' => $copyId
        ));
    }
})->bind('borrow');


$app->post('/borrow', function () use ($app) {

    $post = $app['request']->request;
    if (
        $post->has('borrower') &&
        $post->has('datepickerDebut') &&
        $post->has('datepickerFin') &&
        $post->has('bookId') &&
        $post->has('copyId')
    ) {
        $successBorrow = $app['model']->insertBorrowing($post->get('copyId'), $post->get('borrower'), $post->get('datepickerDebut'), $post->get('datepickerFin'));
        if ($successBorrow)
            return $app->redirect($app["url_generator"]->generate("viewBook", array('bookId' => $post->get('bookId'))));


    }

})->bind('borrowPost');

$app->match('/unborrow/{bookId}/{copyId}', function ($bookId, $copyId) use ($app) {
    $app['model']->returnACopy($copyId);
    return $app->redirect($app["url_generator"]->generate("viewBook", array('bookId' => $bookId)));

})->bind('unborrow');


