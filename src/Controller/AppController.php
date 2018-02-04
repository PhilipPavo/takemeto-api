<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return new Response(
            '<html><body>Takemeto server</body></html>'
        );
    }
}