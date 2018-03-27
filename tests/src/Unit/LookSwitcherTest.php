<?php

namespace Drupal\Tests\look\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\look\Plugin\Block\LookSwitcher;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\look\Plugin\Block\LookSwitcher
 * @group look
 */
class LookSwitcherTest extends UnitTestCase {

  /**
   * The tested look switcher block.
   *
   * @var \Drupal\look\Plugin\Block\LookSwitcher
   */
  protected $lookSwitcher;

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  protected $container;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * The mocked current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $request;

  /**
   * The mocked select query.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $select;

  /**
   * The mocked current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $statement;

  /**
   * The mocked path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
      ->disableOriginalConstructor()
      ->getMock();
    $this->select = $this->createMock('Drupal\Core\Database\Query\SelectInterface');
    $this->statement = $this->createMock('Drupal\Core\Database\StatementInterface');
    $this->pathValidator = $this->createMock('Drupal\Core\Path\PathValidatorInterface');

    $this->container = new ContainerBuilder();
    $this->container->set('path.validator', $this->pathValidator);
    \Drupal::setContainer($this->container);

    $this->connection->method('select')->willReturn($this->select);
    $this->select->method('fields')->willReturnSelf();
    $this->select->method('orderBy')->willReturnSelf();
    $this->select->method('execute')->willReturn($this->statement);

    $this->lookSwitcher = new LookSwitcher([], '', [
      'provider' => 'look',
    ], $this->connection, $this->request);
  }

  /**
   * @covers ::build
   */
  public function testBuild() {
    // Attach all parts for rendering into build array.
    $this->request->method('getRequestUri')->willReturn('/?existing=param');
    $this->statement->method('fetchAllKeyed')->willReturn([
      '1' => 'Look 1',
      '2' => 'Look 2',
      '3' => 'Look 3',
    ]);
    $this->lookSwitcher->setConfiguration([
      'exclude' => [2],
    ]);
    $actual_1 = $this->lookSwitcher->build();
    $expected_1 = [
      '#attributes' => [
        'class' => [
          'look-switcher',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
      [
        '#theme' => 'item_list',
        '#items' => [
          0 => Link::fromTextAndUrl('Look 1', Url::fromUserInput('/', [
            'query' => [
              'existing' => 'param',
              'look' => 'Look 1',
            ],
          ])),
          1 => Link::fromTextAndUrl('Look 3', Url::fromUserInput('/', [
            'query' => [
              'existing' => 'param',
              'look' => 'Look 3',
            ],
          ])),
        ],
        '#type' => 'ul',
        '#context' => [
          'type' => 'look-switcher',
        ],
      ],
    ];
    $this->assertEquals($expected_1, $actual_1);
  }

}
