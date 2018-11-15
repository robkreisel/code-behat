<?php

use Behat\Behat\Tester\Exception\PendingException;
use AppBundle\Entity\Product;
use AppBundle\Entity\User;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;

require_once __DIR__.'/../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawMinkContext implements Context, SnippetAcceptingContext
{
    use KernelDictionary;

    private $currentUser;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @BeforeScenario
     */
    public function clearData()
    {
        $purger = new ORMPurger($this->getContainer()->get('doctrine')->getManager());
        $purger->purge();
    }

    /**
     * @BeforeScenario @fixtures
     */
    public function loadFixtures()
    {
        $loader = new ContainerAwareLoader($this->getContainer());
        $loader->loadFromDirectory(__DIR__.'/../../src/AppBundle/DataFixtures');
        $executor = new ORMExecutor($this->getEntityManager());
        $executor->execute($loader->getFixtures(), true);
    }

    /**
     * @When I fill in the search box with :term
     */
    public function iFillInTheSearchBoxWith($term)
    {
        $searchBox = $this->assertSession()
            ->elementExists('css', 'input[name="searchTerm"]');

        $searchBox->setValue($term);
    }

    /**
     * @When I press the search button
     */
    public function iPressTheSearchButton()
    {
        $button = $this->assertSession()
            ->elementExists('css', '#search_submit');

        $button->press();
    }

    /**
     * @Given there is an admin user :username with password :password
     */
    public function thereIsAnAdminUserWithPassword($username, $password)
    {
        $user = new User();
        $user->setUsername($username);
        $user->setPlainPassword($password);
        $user->setRoles(['ROLE_ADMIN']);

        $em = $this->getContainer()->get('doctrine')
            ->getManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * @Given there is/are :count product(s)
     */
    public function thereAreProducts($count)
    {
        $this->createProducts($count);
    }

    /**
     * @Given I author :count products
     */
    public function iAuthorProducts($count)
    {
        $this->createProducts($count, $this->currentUser);
    }

    /**
     * @Given the following products exist:
     */
    public function theFollowingProductsExist(TableNode $table)
    {
        foreach ($table as $row) {
            $product = new Product();
            $product->setName($row['name']);
            $product->setPrice(rand(10, 1000));
            $product->setDescription('lorem');

            if (isset($row['is published']) && $row['is published'] == 'yes') {
                $product->setIsPublished(true);
            }

            $this->getEntityManager()->persist($product);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * @Then the :rowText row should have a check mark
     */
    public function theRowShouldHaveACheckMark($rowText)
    {
        $row = $this->findRowByText($rowText);
        assertContains('fa-check', $row->getHtml(), 'Could not find the fa-check element in the row!');
    }

    /**
     * @When I press :linkText in the :rowText row
     */
    public function iPressInTheRow($buttonText, $rowText)
    {
        $this->findRowByText($rowText)->pressButton($buttonText);
    }

    /**
     * @When I click on :linkName
     */
    public function iClickOn($linkName)
    {
        $this->getPage()->clickLink($linkName);
    }

    /**
     * @Then I should see :count products
     */
    public function iShouldSeeProducts($count)
    {
        $table = $this->getPage()->find('css', 'table.table');
        assertNotNull($table, 'Cannot find a table!');

        assertCount(intval($count), $table->findAll('css', 'tbody tr'));
    }

    /**
     * @Given I am logged in as an admin
     */
    public function iAmLoggedInAsAnAdmin()
    {
        $this->currentUser = $this->thereIsAnAdminUserWithPassword('admin', 'admin');

        $this->visitPath('/login');
        $this->getPage()->fillField('Username', 'admin');
        $this->getPage()->fillField('Password', 'admin');
        $this->getPage()->pressButton('Login');
    }

    /**
     * @When I wait for the modal to load
     */
    public function iWaitForTheModalToLoad()
    {
        $this->getSession()->wait(
            5000,
            "$('.modal:visible').length > 0"
        );
    }

    private function getPage()
    {
        return $this->getSession()->getPage();
    }

    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    private function createProducts($count, User $author = null)
    {
        for ($i = 0; $i < $count; $i++) {
            $product = new Product();
            $product->setName('Product ' . $i);
            $product->setPrice(rand(10, 1000));
            $product->setDescription('lorem');
            if ($author) {
                $product->setAuthor($author);
            }
            $this->getEntityManager()->persist($product);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @param $rowText
     * @return \Behat\Mink\Element\NodeElement
     */
    private function findRowByText($rowText)
    {
        $row = $this->getPage()->find('css', sprintf('table tr:contains("%s")', $rowText));
        assertNotNull($row, 'Cannot find a table row with this text!');

        return $row;
    }

}
