<?php

declare(strict_types=1);

namespace MSM\DeliveryTable;

use MSM\DeliveryTable\Admin\DocumentationPage;
use MSM\DeliveryTable\Assets\AssetManager;
use MSM\DeliveryTable\Blocks\DeliveryTableBlock;
use MSM\DeliveryTable\Contracts\Registrable;
use MSM\DeliveryTable\Rendering\Template;
use MSM\DeliveryTable\Shipping\FreeShipping\ThresholdDetector;
use MSM\DeliveryTable\Shipping\Method\MethodRepository;
use MSM\DeliveryTable\Shipping\Price\PriceFormatter;
use MSM\DeliveryTable\Shipping\Rule\CoreMethodRules;
use MSM\DeliveryTable\Shipping\Rule\RuleParser;
use MSM\DeliveryTable\Shipping\Tax\ShippingTaxCalculator;
use MSM\DeliveryTable\Shipping\Zone\ZoneLabeller;
use MSM\DeliveryTable\Shipping\Zone\ZoneRepository;
use MSM\DeliveryTable\Shortcodes\DeliveryTableShortcode;
use MSM\DeliveryTable\Support\Requirements;
use MSM\DeliveryTable\Table\DeliveryTableRenderer;
use MSM\DeliveryTable\Table\IntervalBuilder;
use MSM\DeliveryTable\Table\TableFactory;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wires the object graph.
 *
 * Deliberately hand-written rather than reflection based: the dependencies of
 * every service are visible in one screen, and there is no autowiring magic to
 * debug at three in the morning on a shop that is down.
 */
final class Container
{
    /** @var array<string, object> */
    private array $instances = [];

    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl,
        private readonly string $version
    ) {
    }

    /**
     * Services that hook themselves into WordPress, in boot order.
     *
     * @return list<Registrable>
     */
    public function services(): array
    {
        return [
            $this->assets(),
            $this->documentationPage(),
            $this->shortcode(),
            $this->block(),
        ];
    }

    // --- Support -----------------------------------------------------------

    public function requirements(): Requirements
    {
        return $this->share(Requirements::class, static fn (): Requirements => new Requirements());
    }

    public function template(): Template
    {
        return $this->share(Template::class, fn (): Template => new Template($this->basePath . 'templates/'));
    }

    public function assets(): AssetManager
    {
        return $this->share(
            AssetManager::class,
            fn (): AssetManager => new AssetManager($this->baseUrl, $this->basePath, $this->version)
        );
    }

    // --- Shipping domain ---------------------------------------------------

    public function prices(): PriceFormatter
    {
        return $this->share(PriceFormatter::class, static fn (): PriceFormatter => new PriceFormatter());
    }

    public function tax(): ShippingTaxCalculator
    {
        return $this->share(
            ShippingTaxCalculator::class,
            static fn (): ShippingTaxCalculator => new ShippingTaxCalculator()
        );
    }

    public function zones(): ZoneRepository
    {
        return $this->share(ZoneRepository::class, static fn (): ZoneRepository => new ZoneRepository());
    }

    public function zoneLabeller(): ZoneLabeller
    {
        return $this->share(ZoneLabeller::class, static fn (): ZoneLabeller => new ZoneLabeller());
    }

    public function methods(): MethodRepository
    {
        return $this->share(MethodRepository::class, static fn (): MethodRepository => new MethodRepository());
    }

    public function coreMethodRules(): CoreMethodRules
    {
        return $this->share(
            CoreMethodRules::class,
            fn (): CoreMethodRules => new CoreMethodRules($this->methods())
        );
    }

    public function ruleParser(): RuleParser
    {
        return $this->share(
            RuleParser::class,
            fn (): RuleParser => new RuleParser($this->methods(), $this->coreMethodRules())
        );
    }

    public function thresholdDetector(): ThresholdDetector
    {
        return $this->share(
            ThresholdDetector::class,
            fn (): ThresholdDetector => new ThresholdDetector($this->methods(), $this->ruleParser())
        );
    }

    // --- Table -------------------------------------------------------------

    public function intervalBuilder(): IntervalBuilder
    {
        return $this->share(
            IntervalBuilder::class,
            fn (): IntervalBuilder => new IntervalBuilder($this->prices())
        );
    }

    public function tableFactory(): TableFactory
    {
        return $this->share(
            TableFactory::class,
            fn (): TableFactory => new TableFactory(
                $this->ruleParser(),
                $this->thresholdDetector(),
                $this->intervalBuilder(),
                $this->tax(),
                $this->prices()
            )
        );
    }

    public function tableRenderer(): DeliveryTableRenderer
    {
        return $this->share(
            DeliveryTableRenderer::class,
            fn (): DeliveryTableRenderer => new DeliveryTableRenderer(
                $this->zones(),
                $this->zoneLabeller(),
                $this->methods(),
                $this->tableFactory(),
                $this->template(),
                $this->assets()
            )
        );
    }

    // --- Entry points ------------------------------------------------------

    public function shortcode(): DeliveryTableShortcode
    {
        return $this->share(
            DeliveryTableShortcode::class,
            fn (): DeliveryTableShortcode => new DeliveryTableShortcode($this->tableRenderer())
        );
    }

    public function block(): DeliveryTableBlock
    {
        return $this->share(
            DeliveryTableBlock::class,
            fn (): DeliveryTableBlock => new DeliveryTableBlock($this->tableRenderer(), $this->basePath)
        );
    }

    public function documentationPage(): DocumentationPage
    {
        return $this->share(
            DocumentationPage::class,
            fn (): DocumentationPage => new DocumentationPage($this->basePath, $this->assets())
        );
    }

    /**
     * @template T of object
     * @param  class-string<T> $id
     * @param  callable(): T   $factory
     * @return T
     */
    private function share(string $id, callable $factory): object
    {
        /** @var T */
        return $this->instances[$id] ??= $factory();
    }
}
