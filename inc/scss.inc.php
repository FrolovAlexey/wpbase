<?php
if (version_compare(PHP_VERSION, '5.4') < 0) {
    throw new \Exception('scssphp requires PHP 5.4 or above');
}
/**
 * Include scss framewark files
 * get source of scssphp lib from https://github.com/leafo/scssphp
 * @link https://github.com/leafo/scssphp
 */
include_once __DIR__ . '/scss/Base/Range.php';
include_once __DIR__ . '/scss/Block.php';
include_once __DIR__ . '/scss/Colors.php';
include_once __DIR__ . '/scss/Compiler.php';
include_once __DIR__ . '/scss/Compiler/Environment.php';
include_once __DIR__ . '/scss/Exception/CompilerException.php';
include_once __DIR__ . '/scss/Exception/ParserException.php';
include_once __DIR__ . '/scss/Exception/ServerException.php';
include_once __DIR__ . '/scss/Formatter.php';
include_once __DIR__ . '/scss/Formatter/Compact.php';
include_once __DIR__ . '/scss/Formatter/Compressed.php';
include_once __DIR__ . '/scss/Formatter/Crunched.php';
include_once __DIR__ . '/scss/Formatter/Debug.php';
include_once __DIR__ . '/scss/Formatter/Expanded.php';
include_once __DIR__ . '/scss/Formatter/Nested.php';
include_once __DIR__ . '/scss/Formatter/OutputBlock.php';
include_once __DIR__ . '/scss/Node.php';
include_once __DIR__ . '/scss/Node/Number.php';
include_once __DIR__ . '/scss/Parser.php';
include_once __DIR__ . '/scss/Type.php';
include_once __DIR__ . '/scss/Util.php';
include_once __DIR__ . '/scss/Version.php';
include_once __DIR__ . '/scss/Server.php';

use Leafo\ScssPhp\Compiler;

if (! class_exists('scssc', false)) {

    if ( ! class_exists('phpScssMenu') ){
        /**
         * Class for initialization phpScss library
         * Class phpScssMenu
         */
        class phpScssMenu {

            public $scssEnble;
            public $scssType;
            public $scssSource;
            public $scssResult;
            public $scssSchedule;
            public $scssFormat;

            public function __construct(){
                add_action('customize_register', array($this, 'initCustomizeMenuRegister'));
                add_action( 'customize_controls_print_footer_scripts', array($this, 'initCustomizeMenuRegisterScript') );
                add_action( 'customize_controls_print_footer_scripts', array($this, 'initCustomizeMenuRegisterScript') );
                add_action( 'init', array($this, 'setupScss') );

                $this->init();
            }

            /**
             * Initializations data
             */
            public function init() {

                $this->scssEnble = get_theme_mod('enable_scss', false);
                $this->scssSource = get_theme_mod('scss_path', '');
                $this->scssResult = get_theme_mod('scss_path_result', '');
                $this->scssSchedule = get_theme_mod('scss_cache_timestamp', 3600);
                $this->scssFormat = get_theme_mod('scss_format_options', 'Nested');

                if (substr($this->scssResult, 0, -1) != '/') {
                    $this->scssResult .= '/';
                }


                $every_time = get_theme_mod('scss_compil_everytime', false);
                if ( $every_time ) {
                    $this->scssType = 'preload';
                } else {
                    $this->scssType = 'schedule';

                }

            }

            /**
             * setup scss actions
             */
            public function setupScss(){
                switch ($this->scssType) {
                    case 'preload' :
                        add_action( 'wp_enqueue_scripts', array($this, 'compileScss'), 1);
                        break;
                    case 'schedule' :
                        add_filter( 'cron_schedules', array($this, 'scssScheduleInterval') );
                        add_action( 'wp', array($this, 'scssScheduleCronActivation') );
                        add_action( 'admin_init', array($this, 'scssScheduleCronActivation') );
                        add_action( 'scss_schedule_cron', array($this, 'compileScss'));
                        break;
                    default:
                }

            }

            /**
             * set schedule timer
             * @param array $schedules
             * @return array mixed
             */
            public function scssScheduleInterval( $schedules ) {

                $schedules['scssSchedule'] = array(
                    'interval' => (int) $this->scssSchedule,
                    'display'  => esc_html__( 'Scss Schedule interval (' . (int) $this->scssSchedule .  ' seconds)' ),
                );
                return $schedules;
            }

            /**
             * Activate Schedule cron
             */
            public function scssScheduleCronActivation() {

                if (!wp_next_scheduled('scss_schedule_cron')) {
                    wp_schedule_event(time(), 'scssSchedule', 'scss_schedule_cron');
                } else {
                    $schedule = wp_get_schedule('scss_schedule_cron');
                    $schedules = wp_get_schedules();
                    $args  = array();
                    $key = md5(serialize($args));
                    if ( isset($schedules[$schedule])) {
                        $crons = _get_cron_array();
                        if ($crons) {
                            foreach ($crons as $timestamp => $cron) {
                                if (isset($cron['scss_schedule_cron'][$key]) && $cron['scss_schedule_cron'][$key]['interval'] != $schedules[$schedule]['interval']) {

                                    wp_unschedule_event($timestamp, 'scss_schedule_cron', $args);
                                    wp_schedule_event(time(), 'scssSchedule', 'scss_schedule_cron');
                                }
                            }
                        }
                    }
                }
            }

            /**
             * Compile scss to css files
             */
            public function compileScss(){
                if ( $this->scssEnble ) {
                    $directory = get_template_directory() . $this->scssSource;
                    $directory_output = get_template_directory() . $this->scssResult;

                    if (is_dir($directory)) {
                        try {
                            $scss = new Compiler();
                            $scss->setLineNumberStyle(Compiler::LINE_COMMENTS);
                            $scss->addImportPath($directory);

                            $variables = apply_filters('scss_variables', array(

                            ));
                            $scss->setVariables($variables);
                            $scss->setFormatter('Leafo\ScssPhp\Formatter\\' . $this->scssFormat);

                            if ( $files = scandir($directory, 1)) {
                                foreach ($files as $file) {
                                    $file_parts = pathinfo($file);

                                    if ($file_parts['extension'] == 'scss' &&  $file_parts['filename'][0] != '_' ){
                                        $css_file = $scss->compile('@import "' . $file . '";');
                                        $output_file = $directory_output .  $file_parts['filename'] . '.css';
                                        $output_path = dirname($output_file);
                                        $this->createPath($output_path);
                                        file_put_contents($output_file, $css_file);

                                    }
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage();
                            syslog(LOG_ERR, 'scssphp: Unable to compile content');

                        }

                    }
                }
            }

            /**
             * Create path if not exist
             * @param string $path
             * @return bool
             */
            public function createPath($path) {
                if (is_dir($path)) return true;
                $prev_path = substr($path, 0, strrpos($path, '/', -2) + 1 );
                $return = $this->createPath($prev_path);
                return ($return && is_writable($prev_path)) ? mkdir($path) : false;
            }

            /**
             * Add settings to admin customize page
             * @param WP_Customize_Manager $wp_customize
             */
            public function initCustomizeMenuRegister($wp_customize) {

                $wp_customize->add_section( 'theme_scss_options',
                    array(
                        'title' => __( 'Scss Settings', 'scss' ),
                        'priority' => 900,
                        'capability' => 'edit_theme_options',
                        'description' => __('', 'scss'),
                    )
                );

                $wp_customize->add_setting( 'enable_scss',
                    array(
                        'default' => '',
                        'type' => 'theme_mod',
                        'capability' => 'edit_theme_options',
                        'transport' => 'postMessage',
                    )
                );
                $wp_customize->add_control( new WP_Customize_Control(
                    $wp_customize,
                    'theme_enable_scss',
                    array(
                        'label' => __( 'Enable Scss', 'scss' ),
                        'section' => 'theme_scss_options',
                        'type' => 'checkbox',
                        'settings' => 'enable_scss',
                        'priority' => 10,
                    )
                ) );

                $wp_customize->add_setting( 'scss_path',
                    array(
                        'default' => '',
                        'type' => 'theme_mod',
                        'capability' => 'edit_theme_options',
                        'transport' => 'postMessage',
                    )
                );
                $wp_customize->add_control( new WP_Customize_Control(
                    $wp_customize,
                    'theme_scc_path',
                    array(
                        'label' => __( 'Scss source path', 'scss' ),
                        'section' => 'theme_scss_options',
                        'type' => 'text',
                        'settings' => 'scss_path',
                        'priority' => 10,
                    )
                ) );

                $wp_customize->add_setting( 'scss_path_result',
                    array(
                        'default' => '',
                        'type' => 'theme_mod',
                        'capability' => 'edit_theme_options',
                        'transport' => 'postMessage',
                    )
                );
                $wp_customize->add_control( new WP_Customize_Control(
                    $wp_customize,
                    'theme_scc_path_result',
                    array(
                        'label' => __( 'Scss result path', 'scss' ),
                        'section' => 'theme_scss_options',
                        'type' => 'text',
                        'settings' => 'scss_path_result',
                        'priority' => 10,
                    )
                ) );

                $wp_customize->add_setting( 'scss_compil_everytime',
                    array(
                        'default' => '',
                        'type' => 'theme_mod',
                        'capability' => 'edit_theme_options',
                        'transport' => 'postMessage',
                    )
                );
                $wp_customize->add_control( new WP_Customize_Control(
                    $wp_customize,
                    'theme_scc_compil_everytime',
                    array(
                        'label' => __( 'Compilation everytime', 'scss' ),
                        'section' => 'theme_scss_options',
                        'type' => 'checkbox',
                        'settings' => 'scss_compil_everytime',
                        'priority' => 10,
                    )
                ) );

                $wp_customize->add_setting( 'scss_cache_timestamp',
                    array(
                        'default' => '360000',
                        'type' => 'theme_mod',
                        'capability' => 'edit_theme_options',
                        'transport' => 'postMessage',
                    )
                );
                $wp_customize->add_control( new WP_Customize_Control(
                    $wp_customize,
                    'theme_scc_cache_timestamp',
                    array(
                        'label' => __( 'Caxhe timestamp', 'scss' ),
                        'section' => 'theme_scss_options',
                        'type' => 'number',
                        'settings' => 'scss_cache_timestamp',
                        'priority' => 10,
                    )
                ) );

                $wp_customize->add_setting( 'scss_format_options',
                    array(
                        'default' => '',
                        'type' => 'theme_mod',
                        'capability' => 'edit_theme_options',
                        'transport' => 'postMessage',
                    )
                );
                $wp_customize->add_control( new WP_Customize_Control(
                    $wp_customize,
                    'theme_scss_format_options',
                    array(
                        'label' => __( 'Output format', 'scss' ),
                        'section' => 'theme_scss_options',
                        'type' => 'select',
                        'settings' => 'scss_format_options',
                        'priority' => 10,
                        'choices'  => array(
                            'Expanded'  => 'Expanded',
                            'Nested' => 'Nested',
                            'Compresse ' => 'Compressed',
                            'Compact' => 'Compact',
                            'Crunche' => 'Crunched',
                        ),
                    )
                ) );
            }

            /**
             * Add script to admin customize page
             */
            public function initCustomizeMenuRegisterScript(){
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        var enableScssInput = $( '#customize-control-theme_enable_scss input' ),
                            scssSettings = $('#customize-control-theme_scc_path,#customize-control-theme_scc_path_result,#customize-control-theme_scc_compil_everytime,#customize-control-theme_scc_cache_timestamp,#customize-control-theme_scss_format_options'),
                            scssEveryTime = $('#customize-control-theme_scc_compil_everytime input'),
                            scssTimestamp = $('#customize-control-theme_scc_cache_timestamp');

                        scssDisplayControl(scssSettings, enableScssInput.prop( "checked" ));
                        scssDisplayControl(scssTimestamp, scssEveryTime.prop( "checked" ), true);
                        enableScssInput.change(function(){
                            scssDisplayControl(scssSettings, $(this).prop( "checked" ));
                        });
                        scssEveryTime.change(function(){
                            scssDisplayControl(scssTimestamp, $(this).prop( "checked" ), true);
                        });
                        function scssDisplayControl( block, control, revert ){
                            if( control ){
                                if (revert) {
                                    block.hide();
                                } else {
                                    block.show();
                                }
                            }
                            else{
                                if (revert) {
                                    block.show();
                                } else {
                                    block.hide();
                                }
                            }
                        }

                    });
                </script>
                <?php
            }
        }

        new  phpScssMenu();
    }
}

