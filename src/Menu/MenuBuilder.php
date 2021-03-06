<?php

declare(strict_types=1);

namespace Bolt\Menu;

use Bolt\Configuration\Config;
use Bolt\Content\ContentType;
use Bolt\Entity\Content;
use Bolt\Repository\ContentRepository;
use Bolt\Twig\ContentExtension;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Translation\TranslatorInterface;

class MenuBuilder
{
    /** @var FactoryInterface */
    private $menuFactory;

    /** @var Config */
    private $config;

    /** @var Stopwatch */
    private $stopwatch;

    /** @var ContentRepository */
    private $contentRepository;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var TranslatorInterface */
    private $translator;

    /** @var ContentExtension */
    private $contentExtension;

    public function __construct(FactoryInterface $menuFactory, Config $config, Stopwatch $stopwatch, ContentRepository $contentRepository, UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator, ContentExtension $contentExtension)
    {
        $this->menuFactory = $menuFactory;
        $this->config = $config;
        $this->stopwatch = $stopwatch;
        $this->contentRepository = $contentRepository;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->contentExtension = $contentExtension;
    }

    public function createSidebarMenu()
    {
        $this->stopwatch->start('bolt.sidebar');

        $t = $this->translator;

        $menu = $this->menuFactory->createItem('root');

        $menu->addChild('Dashboard', [
            'uri' => $this->urlGenerator->generate('bolt_dashboard'),
            'extras' => [
                'name' => $t->trans('caption.dashboard'),
                'icon' => 'fa-tachometer-alt',
            ],
        ]);

        $menu->addChild('Content', ['extras' => [
            'name' => $t->trans('caption.content'),
            'type' => 'separator',
            'icon' => 'fa-file',
        ]]);

        $contentTypes = $this->config->get('contenttypes');

        foreach ($contentTypes as $contentType) {
            $menu->addChild($contentType['slug'], [
                'uri' => $this->urlGenerator->generate('bolt_content_overview', ['contentType' => $contentType['slug']]),
                'extras' => [
                    'name' => $contentType['name'],
                    'singular_name' => $contentType['singular_name'],
                    'slug' => $contentType['slug'],
                    'singular_slug' => $contentType['singular_slug'],
                    'icon' => $contentType['icon_many'],
                    'link_new' => $this->urlGenerator->generate('bolt_content_new', ['contentType' => $contentType['slug']]),
                    'content_type' => $contentType['slug'],
                    'singleton' => $contentType['singleton'],
                    'active' => $contentType['slug'] === 'pages' ? true : false,
                    'submenu' => $this->getLatestRecords($contentType['slug']),
                ],
            ]);
        }

        $menu->addChild('Settings', ['extras' => [
            'name' => $t->trans('caption.settings'),
            'type' => 'separator',
            'icon' => 'fa-wrench',
        ]]);

        // Configuration submenu

        $menu->addChild('Configuration', ['extras' => [
            'name' => $t->trans('caption.configuration'),
            'icon' => 'fa-sliders-h',
        ]]);

        $menu->getChild('Configuration')->addChild('Users &amp; Permissions', [
            'uri' => $this->urlGenerator->generate('bolt_users'),
            'extras' => [
                'name' => $t->trans('caption.users_permissions'),
                'icon' => 'fa-users',
            ],
        ]);

        $menu->getChild('Configuration')->addChild('Main configuration', [
            'uri' => $this->urlGenerator->generate('bolt_file_edit', [
                'area' => 'config',
                'file' => '/bolt/config.yaml',
            ]),
            'extras' => [
                'name' => $t->trans('caption.main_configuration'),
                'icon' => 'fa-cog',
            ],
        ]);

        $menu->getChild('Configuration')->addChild('ContentTypes', [
            'uri' => $this->urlGenerator->generate('bolt_file_edit', [
                'area' => 'config',
                'file' => '/bolt/contenttypes.yaml',
            ]),
            'extras' => [
                'name' => $t->trans('caption.contenttypes'),
                'icon' => 'fa-object-group',
            ],
        ]);

        $menu->getChild('Configuration')->addChild('Taxonomies', [
            'uri' => $this->urlGenerator->generate('bolt_file_edit', [
                'area' => 'config',
                'file' => '/bolt/taxonomy.yaml',
            ]),
            'extras' => [
                'name' => $t->trans('caption.taxonomies'),
                'icon' => 'fa-tags',
            ],
        ]);

        $menu->getChild('Configuration')->addChild('Menu set up', [
            'uri' => $this->urlGenerator->generate('bolt_file_edit', [
                'area' => 'config',
                'file' => '/bolt/menu.yaml',
            ]),
            'extras' => [
                'name' => $t->trans('caption.menu_setup'),
                'type' => 'separator',
                'icon' => 'fa-list',
            ],
        ]);

        $menu->getChild('Configuration')->addChild('Routing set up', [
            'uri' => $this->urlGenerator->generate('bolt_file_edit', [
                'area' => 'config',
                'file' => '/routes.yaml',
            ]),
            'extras' => [
                'name' => $t->trans('caption.routing_setup'),
                'icon' => 'fa-directions',
            ],
        ]);

        $menu->getChild('Configuration')->addChild('All configuration files', [
            'uri' => $this->urlGenerator->generate('bolt_filemanager', ['area' => 'config']),
            'extras' => [
                'name' => $t->trans('caption.all_configuration_files'),
                'icon' => 'fa-cogs',
            ],
        ]);

        // Maintenance submenu

        $menu->addChild('Maintenance', ['extras' => [
            'name' => $t->trans('caption.maintenance'),
            'icon' => 'fa-tools',
        ]]);

        $menu->getChild('Maintenance')->addChild('Bolt API', [
            'uri' => $this->urlGenerator->generate('api_entrypoint'),
            'extras' => [
                'name' => $t->trans('caption.api'),
                'icon' => 'fa-code',
            ],
        ]);

        $menu->getChild('Maintenance')->addChild('Check database', [
            'uri' => '',
            'extras' => [
                'name' => $t->trans('caption.check_database'),
                'icon' => 'fa-database',
            ],
        ]);

        $menu->getChild('Maintenance')->addChild('Fixtures', [
            'uri' => '',
            'extras' => [
                'name' => $t->trans('caption.fixtures_dummy_content'),
                'icon' => 'fa-hat-wizard',
            ],
        ]);

        $menu->getChild('Maintenance')->addChild('Clear the cache', [
            'uri' => $this->urlGenerator->generate('bolt_clear_cache'),
            'extras' => [
                'name' => $t->trans('caption.clear_cache'),
                'icon' => 'fa-eraser',
            ],
        ]);

        $menu->getChild('Maintenance')->addChild('Installation checks', [
            'uri' => '',
            'extras' => [
                'name' => $t->trans('caption.installation_checks'),
                'icon' => 'fa-clipboard-check',
            ],
        ]);

        $menu->getChild('Maintenance')->addChild('Translations: Messages', [
            'uri' => $this->urlGenerator->generate('translation_index'),
            'extras' => [
                'name' => $t->trans('caption.translations'),
                'icon' => 'fa-language',
            ],
        ]);

        $menu->getChild('Maintenance')->addChild('Extensions', [
            'uri' => '',
            'extras' => [
                'name' => $t->trans('caption.extensions'),
                'icon' => 'fa-plug',
            ],
        ]);

        // @todo When we're close to stable release, make this less prominent
        $menu->getChild('Maintenance')->addChild('The Kitchensink', [
            'uri' => $this->urlGenerator->generate('bolt_kitchensink'),
            'extras' => [
                'name' => $t->trans('caption.kitchensink'),
                'icon' => 'fa-bath',
            ],
        ]);

        $menu->getChild('Maintenance')->addChild('About Bolt', [
            'uri' => $this->urlGenerator->generate('bolt_about'),
            'extras' => [
                'name' => $t->trans('caption.about_bolt'),
                'icon' => 'fa-award',
            ],
        ]);

        // File Management submenu

        $menu->addChild('File Management', ['extras' => [
            'name' => $t->trans('caption.file_management'),
            'icon' => 'fa-folder-open',
        ]]);

        $menu->getChild('File Management')->addChild('Uploaded files', [
            'uri' => $this->urlGenerator->generate('bolt_filemanager', ['area' => 'files']),
            'extras' => [
                'name' => $t->trans('caption.uploaded_files'),
                'icon' => 'fa-archive',
            ],
        ]);

        $menu->getChild('File Management')->addChild('View/edit Templates', [
            'uri' => $this->urlGenerator->generate('bolt_filemanager', ['area' => 'themes']),
            'extras' => [
                'name' => $t->trans('caption.view_edit_templates'),
                'icon' => 'fa-scroll',
            ],
        ]);

        $this->stopwatch->stop('bolt.sidebar');

        return $menu;
    }

    private function getLatestRecords($slug)
    {
        /** @var ContentType $ct */
        $contentType = ContentType::factory($slug, $this->config->get('contenttypes'));

        /** @var Content[] $records */
        $this->stopwatch->start('menuBuilder.findLatest');
        $records = $this->contentRepository->findLatest($contentType, 5);
        $this->stopwatch->stop('menuBuilder.findLatest');

        $result = [];

        $this->stopwatch->start('menuBuilder.parseLatest');
        /** @var Content $record */
        foreach ($records as $record) {
            $result[] = [
                'id' => $record->getId(),
                'name' => $this->contentExtension->getTitle($record),
                'link' => $this->contentExtension->getLink($record),
                'editLink' => $this->contentExtension->getEditLink($record),
                'icon' => $record->getIcon(),
            ];
        }
        $this->stopwatch->stop('menuBuilder.parseLatest');

        return $result;
    }

    public function getMenu()
    {
        $menu = $this->createSidebarMenu()->getChildren();

        $menuData = [];

        foreach ($menu as $child) {
            $submenu = [];

            if ($child->hasChildren()) {
                foreach ($child->getChildren() as $submenuChild) {
                    $submenu[] = [
                        'name' => $submenuChild->getExtra('name') ?: $submenuChild->getLabel(),
                        'icon' => $submenuChild->getExtra('icon'),
                        'editLink' => $submenuChild->getUri(),
                        'active' => $submenuChild->getExtra('active'),
                    ];
                }
            } else {
                $submenu = $child->getExtra('submenu');
            }

            $menuData[] = [
                'name' => $child->getExtra('name') ?: $child->getLabel(),
                'singular_name' => $child->getExtra('singular_name'),
                'slug' => $child->getExtra('slug'),
                'singular_slug' => $child->getExtra('singular_slug'),
                'icon' => $child->getExtra('icon'),
                'link' => $child->getUri(),
                'link_new' => $child->getExtra('link_new'),
                'contenttype' => $child->getExtra('contenttype'),
                'singleton' => $child->getExtra('singleton'),
                'type' => $child->getExtra('type'),
                'active' => $child->getExtra('active'),
                'submenu' => $submenu,
            ];
        }

        return $menuData;
    }
}
