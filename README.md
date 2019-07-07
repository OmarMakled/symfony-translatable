# symfony3 & translation

- [Installation](#installation)
- [TranslatableListener](#translatableListener)
- [Using ORM query hint](#using-orm-query-hint)
- [Doctrine Converter via an Expression](#doctrine-converter-via-an-expression)
- [Eager Loading](#eager-loading)
- [Only one query](#only-one-query)

### Installation

To Install you need to run this command:

```
git clone
cd
composer install
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console server:start
```

### TranslatableListener

To make any field translatable you should add `Gedmo` anotation, It's very import to load translatable before fetching otherwise doctrine won't detected and append any necessary relation to joins with translations table `loadTranslationMetaData`

```
    /**
     * @var string
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;
```

```
    private function loadTranslationMetaData($locale)
    {
        $translatableListener = new TranslatableListener();
        $translatableListener->setTranslatableLocale($locale);
        $translatableListener->setDefaultLocale('en');
        $translatableListener->setTranslationFallback(true);
        $this->_em->getEventManager()->addEventSubscriber($translatableListener);
    }
```

### Using ORM query hint

By default, behind the scenes, when you load a record - translatable hooks into postLoad event and issues additional query to translate all fields. Imagine that, when you load a collection, it may issue a lot of queries just to translate those fields. Including array hydration, it is not possible to hook any postLoad event since it is not an entity being hydrated. These are the main reasons why TranslationWalker was created.

TranslationWalker uses a query hint to hook into any select type query, and when you execute the query, no matter which hydration method you use, it automatically joins the translations for all fields, so you could use ordering filtering or whatever you want on translated fields instead of original record fields.

And in result there is only one query for all this happiness.

If you use translation fallbacks it will be also in the same single query and during the hydration process it will replace the empty fields in case if they do not have a translation in currently used locale.

```
    public function getAll($locale)
    {
        $this->loadTranslationMetaData($locale);

        return $this->_em->createQueryBuilder()
            ->select(['p', 'o', 'c', 'u'])
            ->from('AppBundle:Post', 'p')
            ->innerJoin('p.author', 'o')
            ->innerJoin('p.category', 'c')
            ->innerJoin('o.user', 'u')
            ->getQuery()
            ->setHint(
                TranslatableListener::HINT_TRANSLATABLE_LOCALE,
                $locale
            )
            ->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                TranslationWalker::class
            )
            ->getResult();

    }
```

### Doctrine Converter via an Expression

```

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
```

Use the special @Entity annotation with an expr option to fetch the object by calling a method on your repository. The repository method will be your entity's Repository class and any route wildcards - like {id} are available as variables.

### Eager Loading

Simply by adding `fetch=eager` you make an `inner join` and the mechanism of fetching data with the current structure between table and translation table should be `left join`

### Only one query

Notice how translation bundel fetch data using `LEFT JOIN` and `COALESCE`.

```
SELECT p0_.id AS id_0, COALESCE(t1_.content, p0_.title) AS title_1, COALESCE(t2_.content, p0_.description) AS description_2, a3_.id AS id_3, COALESCE(t4_.content, a3_.firstname) AS firstname_4, COALESCE(t5_.content, a3_.lastname) AS lastname_5, COALESCE(t6_.content, a3_.address) AS address_6, COALESCE(t7_.content, a3_.country) AS country_7, c8_.id AS id_8, COALESCE(t9_.content, c8_.title) AS title_9, COALESCE(t10_.content, c8_.description) AS description_10, u11_.id AS id_11, u11_.email AS email_12, p0_.author_id AS author_id_13, p0_.category_id AS category_id_14, a3_.user_id AS user_id_15 FROM post p0_ INNER JOIN author a3_ ON p0_.author_id = a3_.id INNER JOIN category c8_ ON p0_.category_id = c8_.id INNER JOIN user u11_ ON a3_.user_id = u11_.id LEFT JOIN post_translations t1_ ON t1_.locale = 'ar' AND t1_.field = 'title' AND t1_.object_id = p0_.id LEFT JOIN post_translations t2_ ON t2_.locale = 'ar' AND t2_.field = 'description' AND t2_.object_id = p0_.id LEFT JOIN author_translations t4_ ON t4_.locale = 'ar' AND t4_.field = 'firstname' AND t4_.object_id = a3_.id LEFT JOIN author_translations t5_ ON t5_.locale = 'ar' AND t5_.field = 'lastname' AND t5_.object_id = a3_.id LEFT JOIN author_translations t6_ ON t6_.locale = 'ar' AND t6_.field = 'address' AND t6_.object_id = a3_.id LEFT JOIN author_translations t7_ ON t7_.locale = 'ar' AND t7_.field = 'country' AND t7_.object_id = a3_.id LEFT JOIN category_translations t9_ ON t9_.locale = 'ar' AND t9_.field = 'title' AND t9_.object_id = c8_.id LEFT JOIN category_translations t10_ ON t10_.locale = 'ar' AND t10_.field = 'description' AND t10_.object_id = c8_.id;
```
