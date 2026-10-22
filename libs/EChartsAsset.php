<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;

/**
 * Loads pinned, reproducible ECharts runtimes and official themes.
 */
final class EChartsAsset
{
    public const VERSION = '6.1.0';
    public const CALENDAR_PIE_SHA256 = 'cfa29cc588f00c30c27f5843923f6993717c48aa1b1b5321b1c82a3814568ab9';
    public const STATE_TIMELINE_SHA256 = 'ff7da41ec7d669f7daf7ef2346ee06e4af1b341dcf307a538500184af63c9d39';
    public const THEME_RIVER_SHA256 = 'd4739d560c4fc4ce0ac1ad3f81334aa7c8e10d7143cb86d89853a6008955d8dc';
    public const PICTORIAL_BAR_SHA256 = 'ce03a142a661d6a32ee931a9e6efcb390c311220f1e53cdeb89a32f1bee9ef5d';
    public const CANDLESTICK_SHA256 = '22dfd0a2530eabfb1b2ca3e6cfb16c77b7e5462b66c8ca72c884a3a590a5633e';
    public const FUNNEL_SHA256 = 'da9750dd18363325d23b1bcd4ea3af7e1011620b80b994ad6c2d5e3ee819314d';
    public const SHA256 = '8c6216a27ffe06481fe81ae101cb17c2f39fafe9357c99adc110434f03dd9bbc';
    public const CARTESIAN_SHA256 = '92954fbddaa6b7c09f25819ab7b4743eb6afd5efa92351f129b14d298d2ab31a';
    public const BAR_CATEGORY_SHA256 = '2d0c0742e63d9a044183dc61db8981d57c3d4d2fa1134aa09e2bf0c56411c818';
    public const SCATTER_SHA256 = 'd759229d5b223da5b3badff736a732f978efc30c3e91d8913aa3e90d01b2974a';
    public const HEATMAP_SHA256 = '2ed5eac005bb669e165d16bbd12003bf8e7f6fb6a95a6d103579bad92b1418bc';
    public const POLAR_SHA256 = 'f31daec4db27dee855d736f2443b15b5642011bc99bbe6f9bc1fdb91da1ef69c';
    public const PIE_SHA256 = 'b61d6fb1b4652e2365cb91813ce23cc3e2f5afac06610a47bc0ab9eb706440ea';
    public const TREE_SHA256 = '5debc28928d9d9d51e1611252e494585dc5aa196667542698ee68d40bc22bdb4';
    public const GRAPH_SHA256 = 'b1bc4e3bda0e48a359e3d08d55695894b45e3dc186fbec8bdb4d6265fea23f1b';
    public const TREEMAP_SHA256 = 'e9020d97d1f90d3967dbb717485edcafed098f3ee7b2b632b6f01055582a148e';
    public const SUNBURST_SHA256 = '95ef117d648729e87199ab20a815f773cbe660da2f007fd65441add95c1daa43';
    public const PARALLEL_SHA256 = 'ded2b972e4032a840353eabc34809047d70c5bc9e7f700b6a09f617d9bc21295';
    public const SANKEY_SHA256 = '02c589e73e13a571727f7320e2b7bc859bc3ea6f46914871831f7a562d0b32dc';
    public const RADAR_SHA256 = '95a007a03e78d1b428424548f3d309bf7c0b24125aca436a91434fc9aef3011e';
    public const CALENDAR_SHA256 = '9879dece9194b401d95d4e8061b43bf83bc3d6c08dacb320406f48de5b8d31a5';
    public const BOXPLOT_SHA256 = '485a64860d8f254bdfb18c230fc38ec996c96dea6af29ef0d7830d346fe0d9a6';
    public const TIME_SERIES_SHA256 = self::CARTESIAN_SHA256;
    public const THEME_AUTO = 'auto';
    public const THEME_SHA256 = [
        'dark'        => 'ae60e563617cb87514690c1946ee202e78c9f1487820490614b58934ed037458',
        'vintage'     => '15c22b1f26961f972d23a9297362d42c4e59764e4b38025364f341cf144fb84f',
        'macarons'    => '1aa73f933f0fd92b02e3e1de18299b15a2d536eb29c4b9755b8ab25d54f5640d',
        'infographic' => '43f803926e8625a1f1cbf6b6189d1735c0526e3e9e7e12aa8531a8296550c1b4',
        'shine'       => '33f28aaec40952dbad84de2d8cb3d58f49ea131806fad9497f06ad4d46f2a8f3',
        'roma'        => '01303d34787f242664d5ad35bb1d535bb21d701ff60f6f1412e8c872008b5a05'
    ];

    private const THEME_PALETTES = [
        self::THEME_AUTO => [
            'background'    => '#151619',
            'text'          => '#F4F5F7',
            'muted'         => '#969AA2',
            'border'        => '#A5A9B0',
            'track'         => '#34363B',
            'accent'        => '#55CBB5',
            'surface'       => '#25272B',
            'seriesColors'  => [
                '#5070DD', '#B6D634', '#505372', '#FF994D', '#0CA8DF',
                '#FFD10A', '#FB628B', '#785DB0', '#3FBE95'
            ],
            'gaugeAxisLine' => [[1.0, '#34363B']]
        ],
        'dark' => [
            'background'    => '#100C2A',
            'text'          => '#B9B8CE',
            'muted'         => '#817F91',
            'border'        => '#EEF1FA',
            'track'         => '#484753',
            'accent'        => '#4992FF',
            'surface'       => '#353450',
            'seriesColors'  => [
                '#4992FF', '#7CFFB2', '#FDDD60', '#FF6E76', '#58D9F9',
                '#05C091', '#FF8A45', '#8D48E3', '#DD79FF'
            ],
            'gaugeAxisLine' => [[1.0, '#484753']]
        ],
        'vintage' => [
            'background'    => '#FEF8EF',
            'text'          => '#333333',
            'muted'         => '#6E7074',
            'border'        => '#919E8B',
            'track'         => '#E5DDD2',
            'accent'        => '#D87C7C',
            'surface'       => '#FFFDF9',
            'seriesColors'  => [
                '#D87C7C', '#919E8B', '#D7AB82', '#6E7074', '#61A0A8',
                '#EFA18D', '#787464', '#CC7E63', '#724E58', '#4B565B'
            ],
            'gaugeAxisLine' => [[1.0, '#E5DDD2']]
        ],
        'macarons' => [
            'background'    => '#FFFFFF',
            'text'          => '#008ACD',
            'muted'         => '#8D98B3',
            'border'        => '#5AB1EF',
            'track'         => '#E6F4F5',
            'accent'        => '#2EC7C9',
            'surface'       => '#F6FBFC',
            'seriesColors'  => [
                '#2EC7C9', '#B6A2DE', '#5AB1EF', '#FFB980', '#D87A80',
                '#8D98B3', '#E5CF0D', '#97B552', '#95706D', '#DC69AA',
                '#07A2A4', '#9A7FD1', '#588DD5', '#F5994E', '#C05050',
                '#59678C', '#C9AB00', '#7EB00A', '#6F5553', '#C14089'
            ],
            'gaugeAxisLine' => [
                [0.2, '#2EC7C9'],
                [0.8, '#5AB1EF'],
                [1.0, '#D87A80']
            ]
        ],
        'infographic' => [
            'background'    => '#FFFFFF',
            'text'          => '#27727B',
            'muted'         => '#6E7074',
            'border'        => '#27727B',
            'track'         => '#DEE6E7',
            'accent'        => '#C1232B',
            'surface'       => '#F7F9F9',
            'seriesColors'  => [
                '#C1232B', '#27727B', '#FCCE10', '#E87C25', '#B5C334',
                '#FE8463', '#9BCA63', '#FAD860', '#F3A43B', '#60C0DD',
                '#D7504B', '#C6E579', '#F4E001', '#F0805A', '#26C0C0'
            ],
            'gaugeAxisLine' => [
                [0.2, '#B5C334'],
                [0.8, '#27727B'],
                [1.0, '#C1232B']
            ]
        ],
        'shine' => [
            'background'    => '#FFFFFF',
            'text'          => '#333333',
            'muted'         => '#6E7074',
            'border'        => '#005EAA',
            'track'         => '#DEEAF2',
            'accent'        => '#C12E34',
            'surface'       => '#F5F9FC',
            'seriesColors'  => [
                '#C12E34', '#E6B600', '#0098D9', '#2B821D',
                '#005EAA', '#339CA8', '#CDA819', '#32A487'
            ],
            'gaugeAxisLine' => [
                [0.2, '#2B821D'],
                [0.8, '#005EAA'],
                [1.0, '#C12E34']
            ]
        ],
        'roma' => [
            'background'    => '#FFFFFF',
            'text'          => '#333333',
            'muted'         => '#2E4783',
            'border'        => '#B8D2C7',
            'track'         => '#EEF2EF',
            'accent'        => '#E01F54',
            'surface'       => '#F7F5EF',
            'seriesColors'  => [
                '#E01F54', '#001852', '#F5E8C8', '#B8D2C7', '#C6B38E',
                '#A4D8C2', '#F3D999', '#D3758F', '#DCC392', '#2E4783',
                '#82B6E9', '#FF6347', '#A092F1', '#0A915D', '#EAF889',
                '#6699FF', '#FF6666', '#3CB371', '#D5B158', '#38B6B6'
            ],
            'gaugeAxisLine' => [
                [0.2, '#E01F54'],
                [0.8, '#B8D2C7'],
                [1.0, '#001852']
            ]
        ]
    ];

    public static function JavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.gauge.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts runtime could not be loaded.');
        }
        if (!self::HasExpectedIntegrity($content)) {
            throw new RuntimeException('The bundled Apache ECharts runtime failed its integrity check.');
        }

        return self::NormalizeLineEndings($content);
    }

    public static function CartesianJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.cartesian.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Cartesian runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::CARTESIAN_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Cartesian runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    /** Loads the current Category Bar subset without enlarging existing Cartesian consumers. */
    public static function BarCategoryJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts/' . self::VERSION . '/echarts.bar-category.min.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Category Bar runtime could not be loaded.');
        }
        $canonical = self::NormalizeLineEndings($content);
        if (!hash_equals(self::BAR_CATEGORY_SHA256, hash('sha256', $canonical))) {
            throw new RuntimeException('The bundled Apache ECharts Category Bar runtime failed its integrity check.');
        }

        return $canonical;
    }

    public static function TimeSeriesJavaScript(): string
    {
        return self::CartesianJavaScript();
    }

    /** Scatter adds Visual Map, Brush and Toolbox without enlarging the shared Cartesian Tile payload. */
    public static function ScatterJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.scatter.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Scatter runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::SCATTER_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Scatter runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    /** Loads the pinned Heatmap runtime without enlarging existing chart bundles. */
    public static function HeatmapJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.heatmap.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Heatmap runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::HEATMAP_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Heatmap runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    /** Loads the pinned Polar Bar runtime without changing existing chart bundles. */
    public static function PolarJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.polar.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Polar runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::POLAR_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Polar runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    /** Loads the pinned Pie-family runtime without changing existing chart bundles. */
    public static function PieJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.pie.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Pie runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::PIE_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Pie runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    /** Loads the pinned Tree runtime without enlarging existing chart bundles. */
    public static function TreeJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.tree.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Tree runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::TREE_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Tree runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    /** Loads the pinned Graph runtime without enlarging other chart bundles. */
    public static function GraphJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.graph.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Graph runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::GRAPH_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Graph runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    /** Loads the pinned Treemap runtime without enlarging other chart bundles. */
    public static function TreemapJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.treemap.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Treemap runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::TREEMAP_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Treemap runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    /** Loads the pinned Sunburst runtime without enlarging other chart bundles. */
    public static function SunburstJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.sunburst.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Sunburst runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::SUNBURST_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Sunburst runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    /** Loads the pinned Parallel runtime without enlarging other chart bundles. */
    public static function ParallelJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.parallel.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Parallel runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::PARALLEL_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Parallel runtime failed its integrity check.');
        }

        return $canonicalContent;
    }

    /** Loads the pinned custom StateTimeline runtime without enlarging other chart bundles. */
    public static function StateTimelineJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts/' . self::VERSION . '/echarts.state-timeline.min.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts StateTimeline runtime could not be loaded.');
        }
        $canonical = self::NormalizeLineEndings($content);
        if (!hash_equals(self::STATE_TIMELINE_SHA256, hash('sha256', $canonical))) {
            throw new RuntimeException('The bundled Apache ECharts StateTimeline runtime failed its integrity check.');
        }
        return $canonical;
    }

    /** Loads the pinned ThemeRiver runtime without enlarging existing chart bundles. */
    public static function ThemeRiverJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts/' . self::VERSION . '/echarts.theme-river.min.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts ThemeRiver runtime could not be loaded.');
        }
        $canonical = self::NormalizeLineEndings($content);
        if (!hash_equals(self::THEME_RIVER_SHA256, hash('sha256', $canonical))) {
            throw new RuntimeException('The bundled Apache ECharts ThemeRiver runtime failed its integrity check.');
        }
        return $canonical;
    }

    /** Loads the pinned Sankey runtime without enlarging other chart bundles. */
    public static function SankeyJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.sankey.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Sankey runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::SANKEY_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Sankey runtime failed its integrity check.');
        }
        return $canonicalContent;
    }

    /** Loads the pinned Funnel runtime without enlarging other chart bundles. */
    public static function FunnelJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.funnel.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Funnel runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::FUNNEL_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Funnel runtime failed its integrity check.');
        }
        return $canonicalContent;
    }

    /** Loads the pinned Boxplot runtime without enlarging other chart bundles. */
    public static function BoxplotJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.boxplot.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Boxplot runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::BOXPLOT_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Boxplot runtime failed its integrity check.');
        }
        return $canonicalContent;
    }

    /** Loads the pinned PictorialBar runtime without enlarging other chart bundles. */
    public static function PictorialBarJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.pictorial-bar.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts PictorialBar runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::PICTORIAL_BAR_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts PictorialBar runtime failed its integrity check.');
        }
        return $canonicalContent;
    }

    /** Loads the pinned Candlestick runtime without enlarging other chart bundles. */
    public static function CandlestickJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.candlestick.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Candlestick runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::CANDLESTICK_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Candlestick runtime failed its integrity check.');
        }
        return $canonicalContent;
    }

    /** Loads the pinned Radar runtime without enlarging other chart bundles. */
    public static function RadarJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.radar.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Radar runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::RADAR_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Radar runtime failed its integrity check.');
        }
        return $canonicalContent;
    }

    /** Loads Calendar with native daily pies without changing existing chart runtimes. */
    public static function CalendarPieJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts/' . self::VERSION . '/echarts.calendar-pie.min.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts CalendarPie runtime could not be loaded.');
        }
        $canonical = self::NormalizeLineEndings($content);
        if (!hash_equals(self::CALENDAR_PIE_SHA256, hash('sha256', $canonical))) {
            throw new RuntimeException('The bundled Apache ECharts CalendarPie runtime failed its integrity check.');
        }
        return $canonical;
    }

    /** Loads the pinned Calendar runtime without enlarging the hourly Heatmap bundle. */
    public static function CalendarJavaScript(): string
    {
        $path = __DIR__ . '/echarts/' . self::VERSION . '/echarts.calendar.min.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The bundled Apache ECharts Calendar runtime could not be loaded.');
        }
        $canonicalContent = self::NormalizeLineEndings($content);
        if (!hash_equals(self::CALENDAR_SHA256, hash('sha256', $canonicalContent))) {
            throw new RuntimeException('The bundled Apache ECharts Calendar runtime failed its integrity check.');
        }
        return $canonicalContent;
    }

    /**
     * Loads project-owned design utilities shared by ECharts renderers.
     */
    public static function DesignJavaScript(): string
    {
        $path = __DIR__ . '/echarts-design.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The shared ECharts design utilities could not be loaded.');
        }

        return self::NormalizeLineEndings($content);
    }

    /** Loads the DOM adapter shared by families with source selection and pages. */
    public static function SourceControlsJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts-source-controls.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The shared source controls could not be loaded.');
        }
        return self::NormalizeLineEndings($content);
    }

    public static function SourceControlsCSS(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts-source-controls.css');
        if ($content === false || $content === '') {
            throw new RuntimeException('The shared source control style could not be loaded.');
        }
        return self::NormalizeLineEndings($content);
    }

    /** Loads category-window state only for chart families which use it. */
    public static function CategoryNavigationJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts-category-navigation.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The shared category navigation utilities could not be loaded.');
        }
        return self::NormalizeLineEndings($content);
    }

    /** Loads tree-specific view state without growing unrelated chart documents. */
    public static function TreeSupportJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts-tree.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The shared Tree utilities could not be loaded.');
        }
        return self::NormalizeLineEndings($content);
    }

    /** Loads graph view state and label handling without growing unrelated chart documents. */
    public static function GraphSupportJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts-graph.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The shared Graph utilities could not be loaded.');
        }
        return self::StartupJavaScript() . "\n" . self::NormalizeLineEndings($content);
    }

    /** Loads original-value formatting only for the two manual hierarchy families. */
    public static function HierarchyValuesJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts-hierarchy-values.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The hierarchy value formatting script could not be loaded.');
        }
        return self::NormalizeLineEndings($content);
    }

    /** Loads ID-stable selection and legend navigation shared by chart families. */
    public static function SeriesSelectionJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts-series-selection.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The shared series selection utilities could not be loaded.');
        }
        return self::NormalizeLineEndings($content);
    }

    /** Loads lossless startup decoding for families with compressed initial states. */
    public static function StartupJavaScript(): string
    {
        $content = @file_get_contents(__DIR__ . '/echarts-startup.js');
        if ($content === false || $content === '') {
            throw new RuntimeException('The shared startup utilities could not be loaded.');
        }
        return self::NormalizeLineEndings($content);
    }

    /** Loads the shared time-axis zoom controls used by Cartesian renderers. */
    public static function ZoomJavaScript(): string
    {
        $path = __DIR__ . '/echarts-zoom.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The shared ECharts zoom controls could not be loaded.');
        }

        return self::NormalizeLineEndings($content);
    }

    /** Loads the shared, local SVG image-pattern renderer. */
    public static function PatternJavaScript(): string
    {
        $path = __DIR__ . '/echarts-pattern.js';
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('The shared ECharts pattern utilities could not be loaded.');
        }

        return self::NormalizeLineEndings($content);
    }

    /** @return list<string> */
    public static function ThemeIDs(): array
    {
        return [self::THEME_AUTO, ...array_keys(self::THEME_SHA256)];
    }

    public static function IsSupportedTheme(string $themeID): bool
    {
        return in_array($themeID, self::ThemeIDs(), true);
    }

    /**
     * Loads all pinned official themes so an open HTML-SDK tile can switch
     * themes after ApplyChanges without loading external resources.
     */
    public static function ThemeJavaScript(): string
    {
        $themes = [];
        foreach (array_keys(self::THEME_SHA256) as $themeID) {
            $path = __DIR__ . '/echarts/' . self::VERSION . '/themes/' . $themeID . '.js';
            $content = @file_get_contents($path);
            if ($content === false || $content === '') {
                throw new RuntimeException('The bundled Apache ECharts theme could not be loaded: ' . $themeID);
            }
            if (!self::HasExpectedThemeIntegrity($themeID, $content)) {
                throw new RuntimeException('The bundled Apache ECharts theme failed its integrity check: ' . $themeID);
            }

            $themes[] = self::NormalizeLineEndings($content);
        }

        return implode("\n;\n", $themes);
    }

    public static function HasExpectedThemeIntegrity(string $themeID, string $content): bool
    {
        $expectedHash = self::THEME_SHA256[$themeID] ?? null;
        if ($expectedHash === null) {
            return false;
        }

        $canonicalContent = self::NormalizeLineEndings($content);
        if (str_contains($canonicalContent, "\r")) {
            return false;
        }

        return hash_equals($expectedHash, hash('sha256', $canonicalContent));
    }

    /**
     * Returns stable colors for the lightweight SVG form preview. The native
     * tile continues to use the actual registered Apache ECharts theme.
     *
     * @return array{background:string,text:string,muted:string,border:string,track:string,accent:string,surface:string,seriesColors:list<string>,gaugeAxisLine:list<array{float,string}>}
     */
    public static function ThemePreviewPalette(string $themeID): array
    {
        $palette = self::THEME_PALETTES[$themeID] ?? null;
        if ($palette === null) {
            throw new RuntimeException('The requested ECharts theme is not supported: ' . $themeID);
        }

        return $palette;
    }

    /**
     * Returns all stable palettes used to keep custom layouts readable when an
     * upstream theme assumes a different background or component geometry.
     *
     * @return array<string,array{background:string,text:string,muted:string,border:string,track:string,accent:string,surface:string,seriesColors:list<string>,gaugeAxisLine:list<array{float,string}>}>
     */
    public static function ThemePalettes(): array
    {
        return self::THEME_PALETTES;
    }

    public static function ColorToHex(int $color): string
    {
        return sprintf('#%06X', max(0, min(0xFFFFFF, $color)));
    }

    /**
     * Verifies the upstream bytes while tolerating a Windows checkout that
     * converted the original LF line endings to CRLF.
     */
    public static function HasExpectedIntegrity(string $content): bool
    {
        $canonicalContent = self::NormalizeLineEndings($content);
        if (str_contains($canonicalContent, "\r")) {
            return false;
        }

        return hash_equals(self::SHA256, hash('sha256', $canonicalContent));
    }

    private static function NormalizeLineEndings(string $content): string
    {
        return str_replace("\r\n", "\n", $content);
    }
}
