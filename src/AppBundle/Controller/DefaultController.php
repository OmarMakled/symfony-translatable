<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use AppBundle\Entity\AuthorTranslation;
use AppBundle\Entity\Category;
use AppBundle\Entity\CategoryTranslation;
use AppBundle\Entity\Post;
use AppBundle\Entity\PostTranslation;
use AppBundle\Entity\User;
use Faker\Factory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/{locale}", name="homepage")
     */
    public function indexAction($locale = 'en')
    {
        $repository = $this->getDoctrine()->getRepository(Post::class);
        $posts = $repository->getAll($locale);

        return $this->render('default/index.html.twig', [
            'posts' => $posts,
            'locale' => $locale,
        ]);
    }

    /**
     * @Route("/{locale}/actions/create", name="create")
     */
    public function createAction($locale)
    {
        $em = $this->getDoctrine()->getManager();
        $faker = Factory::create();
        $fakerAr = Factory::create('ar_SA');

        $user = new User();
        $user->setEmail($faker->email);
        $em->persist($user);

        $author = new Author();
        $author->setFirstname($faker->name);
        $author->setLastname($faker->name);
        $author->setAddress($faker->address);
        $author->setCountry($faker->country);
        $author->setUser($user);
        $author->addTranslation(new AuthorTranslation('ar', 'firstname', $fakerAr->name));
        $author->addTranslation(new AuthorTranslation('ar', 'lastname', $fakerAr->name));
        $author->addTranslation(new AuthorTranslation('ar', 'address', $fakerAr->address));
        $author->addTranslation(new AuthorTranslation('ar', 'country', $fakerAr->country));
        $em->persist($author);

        $category = new Category();
        $category->setTitle($faker->sentence(2));
        $category->setDescription($faker->text(255));
        $category->addTranslation(new CategoryTranslation('ar', 'title', $fakerAr->realText(20)));
        $category->addTranslation(new CategoryTranslation('ar', 'description', $fakerAr->realText));
        $em->persist($category);

        $post = new Post();
        $post->setTitle($faker->sentence(2));
        $post->setDescription($faker->text(255));
        $post->setAuthor($author);
        $post->setCategory($category);
        $post->addTranslation(new PostTranslation('ar', 'title', $fakerAr->realText(20)));
        $post->addTranslation(new PostTranslation('ar', 'description', $fakerAr->realText));

        $em->persist($post);
        $em->flush();

        return $this->redirectToRoute('homepage', compact('locale'));

    }

    /**
     * @Route("/{locale}/show/{id}", name="show")
     * @Entity("post", expr="repository.getById(id, locale)")
     */
    public function showAction($locale, Post $post)
    {
        return $this->render('default/show.html.twig', [
            'post' => $post,
            'locale' => $locale,
        ]);
    }
}
