<?php

namespace Melanomamine\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{

    public function homeAction()
    {
        $respuesta = $this->render('MelanomamineFrontendBundle:Default:home.html.twig');
        return $respuesta;
    }
}
