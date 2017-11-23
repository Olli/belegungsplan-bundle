<?php
/*
* This file is part of Contao.
*
* Copyright (c) 2017 Jan Karai
*
* @license LGPL-3.0+
*/
namespace Mailwurm\BelegungsplanBundle\Tests\Picker;
use Contao\BackendUser;
use Contao\CoreBundle\Picker\PickerConfig;
use Mailwurm\BelegungsplanBundle\Picker\BelegungsplanPickerProvider;
use Knp\Menu\FactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
/**
* Tests the BelegungsplanPickerProvider class.
*
* @author Jan Karai <http://www.sachsen-it.de>
*/
class BelegungsplanPickerProviderTest extends TestCase {
    /**
    * @var BelegungsplanPickerProvider
    */
    protected $provider;
    /**
    * {@inheritdoc}
    */
    protected function setUp() {
        parent::setUp();
        $menuFactory = $this->createMock(FactoryInterface::class);
        $menuFactory
            ->method('createItem')
            ->willReturnArgument(1)
        ;
        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(
                function ($name, array $params) {
                    return $name.'?'.http_build_query($params);
                }
            )
        ;
        $this->provider = new BelegungsplanPickerProvider($menuFactory, $router);
        $GLOBALS['TL_LANG']['MSC']['belegungsplanPicker'] = 'Belegungsplan picker';
    }
    /**
    * {@inheritdoc}
    */
    protected function tearDown() {
        parent::tearDown();
        unset($GLOBALS['TL_LANG']);
    }
    /**
    * Tests the object instantiation.
    */
    public function testCanBeInstantiated() {
        $this->assertInstanceOf('Mailwurm\BelegungsplanBundle\Picker\BelegungsplanPickerProvider', $this->provider);
    }
    /**
    * Tests the createMenuItem() method.
    */
    public function testCreatesTheMenuItem() {
        $picker = json_encode([
            'context' => 'link',
            'extras' => [],
            'current' => 'belegungsplanPicker',
            'value' => '',
        ]);
        if (function_exists('gzencode') && false !== ($encoded = @gzencode($picker))) {
            $picker = $encoded;
        }
        $this->assertSame(
            [
                'label' => 'Belegungsplan picker',
                'linkAttributes' => ['class' => 'belegungsplanPicker'],
                'current' => true,
                'uri' => 'contao_backend?do=belegungsplan&popup=1&picker='.urlencode(strtr(base64_encode($picker), '+/=', '-_,')),
            ], $this->provider->createMenuItem(new PickerConfig('link', [], '', 'belegungsplanPicker'))
        );
    }
    /**
    * Tests the isCurrent() method.
    */
    public function testChecksIfAMenuItemIsCurrent() {
        $this->assertTrue($this->provider->isCurrent(new PickerConfig('link', [], '', 'belegungsplanPicker')));
        $this->assertFalse($this->provider->isCurrent(new PickerConfig('link', [], '', 'filePicker')));
    }
    /**
    * Tests the getName() method.
    */
    public function testReturnsTheCorrectName() {
        $this->assertSame('belegungsplanPicker', $this->provider->getName());
    }
    /**
    * Tests the supportsContext() method.
    */
    public function testChecksIfAContextIsSupported() {
        $user = $this
            ->getMockBuilder(BackendUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasAccess'])
            ->getMock()
        ;
        $user
            ->method('hasAccess')
            ->willReturn(true)
        ;
        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user)
        ;
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;
        $this->provider->setTokenStorage($tokenStorage);
        $this->assertTrue($this->provider->supportsContext('link'));
        $this->assertFalse($this->provider->supportsContext('file'));
    }
    /**
    * Tests the supportsContext() method without token storage.
    */
    public function testFailsToCheckTheContextIfThereIsNoTokenStorage() {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('No token storage provided');
        $this->provider->supportsContext('link');
    }
    /**
    * Tests the supportsContext() method without token.
    */
    public function testFailsToCheckTheContextIfThereIsNoToken() {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn(null)
        ;
        $this->provider->setTokenStorage($tokenStorage);
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('No token provided');
        $this->provider->supportsContext('link');
    }
    /**
    * Tests the supportsContext() method without a user object.
    */
    public function testFailsToCheckTheContextIfThereIsNoUser() {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn(null)
        ;
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;
        $this->provider->setTokenStorage($tokenStorage);
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The token does not contain a back end user object');
        $this->provider->supportsContext('link');
    }
    /**
    * Tests the supportsValue() method.
    */
    public function testChecksIfAValueIsSupported() {
        $this->assertTrue($this->provider->supportsValue(new PickerConfig('link', [], '{{belegungsplan_url::5}}')));
        $this->assertFalse($this->provider->supportsValue(new PickerConfig('link', [], '{{link_url::5}}')));
    }
    /**
    * Tests the getDcaTable() method.
    */
    public function testReturnsTheDcaTable()
    {
        $this->assertSame('tl_belegungsplan_category', $this->provider->getDcaTable());
    }
    /**
    * Tests the getDcaAttributes() method.
    */
    public function testReturnsTheDcaAttributes() {
        $this->assertSame(
            [
                'fieldType' => 'radio',
                'value' => '5',
            ],
            $this->provider->getDcaAttributes(new PickerConfig('link', [], '{{belegungsplan_url::5}}'))
        );
        $this->assertSame(
            ['fieldType' => 'radio'],
            $this->provider->getDcaAttributes(new PickerConfig('link', [], '{{link_url::5}}'))
        );
    }
    /**
    * Tests the convertDcaValue() method.
    */
    public function testConvertsTheDcaValue()
    {
        $this->assertSame('{{belegungsplan_url::5}}', $this->provider->convertDcaValue(new PickerConfig('link'), 5));
    }
}