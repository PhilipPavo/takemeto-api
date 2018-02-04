<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Routing\Annotation\Route;

class OrderController extends Controller
{
    function validateAuto($autoId, ExecutionContextInterface $context, $payload)
    {
        $store = new DataStore();
        $autos = $store->get('AUTOS_LIST');

        foreach ($autos as $key => $auto){
            if($auto['id'] == $autoId){
                return;
            }
        }

        $context->buildViolation('Auto not found')
            ->atPath('auto')
            ->addViolation();
    }

    function validateCity($cityId, ExecutionContextInterface $context, $payload){
        $store = new DataStore();
        $cities = $store->get('CITIES_LIST');

        foreach ($cities as $key => $city){
            if($city['id'] == $cityId){
                return;
            }
        }

        $context->buildViolation('City not found')
            ->addViolation();
    }

    /**
     * @Route("/order", name="order")
     */
    public function index(Request $request, \Swift_Mailer $mailer)
    {

        $validator = Validation::createValidator();

        $order = json_decode($request->getContent(), true);

        $validator = Validation::createValidator();

        $constraint = new Assert\Collection(array(
            'order' => new Assert\Collection(array(
                'city_from' => new Assert\Callback(array(
                    'callback' => array($this, 'validateCity')
                )),
                'city_to' => new Assert\Callback(array(
                    'callback' => array($this, 'validateCity')
                )),
                'option_gps' => new Assert\Type(array('type' => 'boolean')),
                'option_wifi' => new Assert\Type(array('type' => 'boolean')),
                'option_child_buster' => new Assert\Type(array('type' => 'boolean')),
                'option_child_chair' => new Assert\Type(array('type' => 'boolean')),
                'date_range' => new Assert\Collection(array(
                    'from' => new Assert\DateTime(array(
                        'format' => \DateTime::ISO8601
                    )),
                    'to' => new Assert\DateTime(array(
                        'format' => \DateTime::ISO8601
                    ))
                )),
                'customer' => new Assert\Collection(array(
                    'name' => new Assert\Length(array('min' => 1)),
                    'phone' => new Assert\Length(array('min' => 1)),
                    'email' => new Assert\Email(),
                    'description' => new Assert\Type(array('type' => 'string'))
                )),

                'auto' => new Assert\Callback(array(
                    'callback' => array($this, 'validateAuto')
                ))
            ))
        ));

        $violations = $validator->validate($order, $constraint);

        if(count($violations)){
            $response = $this->json(array(
                'error' => true,
                'violations' => $violations
            ));
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Auth-Token');
            return $response;
        }

        $message = (new \Swift_Message('Hello Email'))
            ->setFrom('send@takemeto.ru')
            ->setTo('pavophilip@gmail.com')
            ->setBody(
                $this->renderView(
                // templates/emails/registration.html.twig
                    'emails/order.html.twig'
                ),
                'text/html'
            )
        ;

        $mailer->send($message);

        $response = $this->json(array(
            'error' => false
        ));

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Auth-Token');

        return $response;
    }
}

class DataStore{
    private $data;

    public function __construct()
    {
        $this->data = json_decode(file_get_contents('./data.json'), true);
    }

    public function get($key){
        if($data = $this->data[$key]){
            return $data;
        }
    }
}
