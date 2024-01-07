<?php
// src/Controller/AddressBookController.php
namespace App\Controller;
use App\Entity\AddressBook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\AddressBookType;

class AddressBookController extends AbstractController
{
    #[Route('/new', name: 'address_book_new')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {               
        $address_book = new AddressBook;
        $form = $this->createForm(AddressBookType::class, $address_book);
        $request->request->set('surname', null); 
        $request->request->set('email', '---');


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $address_book = $form->getData();
            //$address_book->setSurname($request->request->get('surname'));
            $entityManager->persist($address_book);
            $entityManager->flush();
            return $this->redirectToRoute('address_book_table');           
        }

        return $this->render('address_book/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/del/{id}', name: 'address_book_delete')]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {        
        $row = $entityManager->getRepository(AddressBook::class)->find($id);
        if (!$row) {
            throw $this->createNotFoundException('User '.$id.' doesn\'t exist.');
        }
        $entityManager->remove($row);
        $entityManager->flush();
        return $this->redirectToRoute('address_book_table');                
    }

    #[Route('/{name}/{id}', name: 'address_book_update')]
    public function update(Request $request, EntityManagerInterface $entityManager, int $id, string $name): Response
    {        
        $address_book = $entityManager->getRepository(AddressBook::class)->find($id);        
        if (!$address_book) {
            throw $this->createNotFoundException('No data for id '.$id);
        }

        $form = $this->createForm(AddressBookType::class, $address_book);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $address_book = $form->getData();
            $entityManager->persist($address_book);
            $entityManager->flush();
            return $this->redirectToRoute('address_book_table');           
        }
       
        return $this->render('address_book/form.html.twig', [
            'form' => $form,
        ]);                      
    }

    #[Route('/', name: 'address_book_table')]
    public function table() : Response
    {
        return $this->redirectToRoute('address_book_table_from',['from'=>1]);
    }

    #[Route('/{from}', name: 'address_book_table_from')]
    public function table_from(EntityManagerInterface $entityManager, int $from): Response
    {
        $onpage = 20;
        $first = $from;
        $sum = $entityManager->getRepository(AddressBook::class)->count([]);
        if ($first<1) {
            return $this->redirectToRoute('address_book_table_from',['from'=>1]);
        }
        if ($first>$sum) {
            return $this->redirectToRoute('address_book_table_from',['from'=>$sum-$onpage]);
        }
        $last = $first+$onpage-1;
        if ($last>$sum) {$last=$sum;}

        $next = $first+$onpage;
        $prev = $first-$onpage;  
        if($prev<1) {$prev=1;}
        //$address_book = $entityManager->getRepository(AddressBook::class)->findAll();
       
        $address_book = $entityManager->
        getRepository(AddressBook::class)->findBy([], ['surname' => 'ASC'], $onpage, $first-1);                                
       
        return $this->render('address_book/table.html.twig', [
            'data' => $address_book,
            'sum' => $sum,
            'first' => $first,
            'last' => $last,
            'next' => $next,
            'prev' => $prev,
            'onpage' => $onpage,
        ]);
    }
}