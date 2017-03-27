<?php
if ( ! class_exists( 'ThachPham_Theme_Options' ) ) {

    /* class ThachPham_Theme_Options sẽ chứa toàn bộ code tạo options trong theme từ Redux Framework */
    class ThachPham_Theme_Options {
        /* Tái tạo các biến có trong Redux Framework */
        public $args = array();
        public $sections = array();
        public $theme;
        public $ReduxFramework;

        /* Load Redux Framework */
        public function __construct() {

            if ( ! class_exists( 'ReduxFramework' ) ) {
                return;
            }

            // This is needed. Bah WordPress bugs.  <img draggable="false" class="emoji" alt="😉" src="https://s.w.org/images/core/emoji/2.2.1/svg/1f609.svg">
            if ( true == Redux_Helpers::isTheme( __FILE__ ) ) {
                $this->initSettings();
            } else {
                add_action( 'plugins_loaded', array( $this, 'initSettings' ), 10 );
            }

        }

        /**
        Thiết lập các method muốn sử dụng
        Method nào được khai báo trong này thì cũng phải được sử dụng
         **/
        public function initSettings() {

            // Set the default arguments
            $this->setArguments();

            // Set a few help tabs so you can see how it's done
            $this->setHelpTabs();

            // Create the sections and fields
            $this->setSections();

            if ( ! isset( $this->args['opt_name'] ) ) { // No errors please
                return;
            }

            $this->ReduxFramework = new ReduxFramework( $this->sections, $this->args );
        }

        /**
        Thiết lập cho method setAgruments
        Method này sẽ chứa các thiết lập cơ bản cho trang Options Framework như tên menu chẳng hạn
         **/
        public function setArguments()
        {
            $theme = wp_get_theme(); // Lưu các đối tượng trả về bởi hàm wp_get_theme() vào biến $theme để làm một số việc tùy thích.
            $this->args = array(
                // Các thiết lập cho trang Options
                'opt_name' => 'tp_options', // Tên biến trả dữ liệu của từng options, ví dụ: tp_options['field_1']
                'display_name' => $theme->get('Name'), // Thiết lập tên theme hiển thị trong Theme Options
                'menu_type' => 'menu',
                'allow_sub_menu' => true,
                'menu_title' => __('TP Theme Options', 'thachpham'),
                'page_title' => __('TP Theme Options', 'thachpham'),
                'dev_mode' => false,
                'customizer' => true,
                'google_api_key' => 'AIzaSyAs0iVWrG4E_1bG244-z4HRKJSkg7JVrVQ',
                'menu_icon' => '', // Đường dẫn icon của menu option
                // Chức năng Hint tạo dấu chấm hỏi ở mỗi option để hướng dẫn người dùng */
                'hints' => array(
                    'icon' => 'icon-question-sign',
                    'icon_position' => 'right',
                    'icon_color' => 'lightgray',
                    'icon_size' => 'normal',
                    'tip_style' => array(
                        'color' => 'light',
                        'shadow' => true,
                        'rounded' => false,
                        'style' => '',
                    ),
                    'tip_position' => array(
                        'my' => 'top left',
                        'at' => 'bottom right',
                    ),
                    'tip_effect' => array(
                        'show' => array(
                            'effect' => 'slide',
                            'duration' => '500',
                            'event' => 'mouseover',
                        ),
                        'hide' => array(
                            'effect' => 'slide',
                            'duration' => '500',
                            'event' => 'click mouseleave',
                        ),
                    ),
                ) // end Hints
            );
        }

        /**
        Thiết lập khu vực Help để hướng dẫn người dùng
         **/
        public function setHelpTabs() {

            // Custom page help tabs, displayed using the help API. Tabs are shown in order of definition.
            $this->args['help_tabs'][] = array(
                'id'      => 'redux-help-tab-1',
                'title'   => __( 'Theme Information 1', 'thachpham' ),
                'content' => __( '<p>This is the tab content, HTML is allowed.</p>', 'thachpham' )
            );

            $this->args['help_tabs'][] = array(
                'id'      => 'redux-help-tab-2',
                'title'   => __( 'Theme Information 2', 'thachpham' ),
                'content' => __( '<p>This is the tab content, HTML is allowed.</p>', 'thachpham' )
            );

            // Set the help sidebar
            $this->args['help_sidebar'] = __( '<p>This is the sidebar content, HTML is allowed.</p>', 'thachpham' );
        }

        /**
        Thiết lập từng phần trong khu vực Theme Options
        mỗi section được xem như là một phân vùng các tùy chọn
        Trong mỗi section có thể sẽ chứa nhiều field
         **/
        public function setSections() {

            // Home Section
            $this->sections[] = array(
                'title'  => __( 'Header', 'thachpham' ),
                'desc'   => __( 'All of settings for header on this theme.', 'thachpham' ),
                'icon'   => 'el-icon-home',
                'fields' => array(// Mỗi array là một field
                    array(
                        'id'       => 'logo-on',
                        'type'     => 'switch',
                        'title'    => __( 'Enable Image Logo', 'thachpham' ),
                        'compiler' => 'bool', // Trả về giá trị kiểu true/false (boolean)
                        'desc'     => __( 'Do you want to use image as a logo?', 'thachpham' ),
                        'on' => __( 'Enabled', 'thachpham' ),
                        'off' => __('Disabled')
                    ),

                    array(
                        'id'       => 'logo-image',
                        'type'     => 'media',
                        'title'    => __( 'Logo Image', 'thachpham' ),
                        'desc'     => __( 'Image that you want to use as logo', 'thachpham' ),
                    ),
                )
            ); // end section

            // Typography Section
            $this->sections[] = array(
                'title' => __( 'Typography', 'thachpham' ),
                'desc' => __( 'All of settings for themes typography', 'thachpham' ),
                'icon' => 'el-icon-font',
                'fields' => array(
                    // Main typography
                    array(
                        'id' => 'typo-main',
                        'type' => 'typography',
                        'title' => 'Main Typography',
                        'output' => array( 'body' ),
                        'text-transform' => true,
                        'default' => array(
                            'font-size' => '14px',
                            'font-family' => 'Helvetica Neue, Arial, sans-serif',
                            'font-color' => '#333333',
                        ),
                    ),
                )
            ); // end section

        }
    }

    /* Kích hoạt class ThachPham_Theme_Options vào Redux Framework */
    global $reduxConfig;
    $reduxConfig = new ThachPham_Theme_Options();
}