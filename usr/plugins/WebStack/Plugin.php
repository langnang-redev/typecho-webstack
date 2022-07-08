<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Typecho WebStack 导航主题配套插件
 * 
 * @package WebStack
 * @author gogobody
 * @version 1.0.0
 * @link https://github.com/gogobody/WebStack
 */

/**
 * 友情链接改自 Hanny version 1.1.2
 * @author gogobody
 * Class WebStack_Plugin
 */
require_once 'pages/widget/oneArchive.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'pages/widget/Widget_Contents_Post_Admin_Redefine.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'pages/widget/Widget_Contents_Post_Edit_Redefine.php';

class WebStack_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {

        Typecho_Plugin::factory('admin/write-post.php')->option_100 = array('WebStack_Plugin','write_post_option');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write_100 = array('WebStack_Plugin', 'widget_content_post_edit_write');
        Typecho_Plugin::factory('Widget_Archive')->query_100 = array('WebStack_Plugin','handleArchiveQuery');
        Typecho_Plugin::factory('Widget_Archive')->handleInit_100 = array('WebStack_Plugin','handleArchivehandleInit');
        Typecho_Plugin::factory('Widget_Archive')->handle_100 = array('WebStack_Plugin','handle');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish_100 = array('WebStack_Plugin', 'handlePostPublishFinish');
        Typecho_Plugin::factory('admin/footer.php')->end_100 = array('WebStack_Plugin','end_redefine');
        WebStack_Plugin::addPanels();
        // Links
        $info = WebStack_Plugin::linksInstall();
        Helper::addAction('webstack-action', 'WebStack_Action');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx_100 = array('WebStack_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx_100 = array('WebStack_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx_100 = array('WebStack_Plugin', 'parse');

    }

    public static function addPanels()
    {
        Helper::addPanel(3, 'WebStack/pages/admin/write-navi.php', '添加导航链接', '添加导航链接', 'administrator'); //editor //contributor
        Helper::addPanel(3, 'WebStack/pages/admin/manage-navi.php', '管理导航链接', '管理导航链接', 'administrator'); //editor //contributor
        Helper::addPanel(3, 'WebStack/pages/admin/manage-links.php', '管理友情链接', '管理友情链接', 'administrator');

    }
    public static function rmPanels()
    {
        Helper::removePanel(3, 'WebStack/pages/admin/write-navi.php');
        Helper::removePanel(3, 'WebStack/pages/admin/manage-navi.php');
        Helper::removePanel(3, 'WebStack/pages/admin/manage-links.php');
        Helper::removePanel(3, 'WebStack/pages/admin/manage-posts.php');
        Helper::removePanel(3, 'WebStack/pages/admin/write-post.php');

    }

    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        WebStack_Plugin::rmPanels();
        Helper::removeAction("links-edit");
        Helper::removeAction("webstack-action");

    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {

    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * admin write post option
     * 
     * @access public
     * @return void
     */
    public static function write_post_option($post)
    {
        ?>
        <section class="typecho-post-option">
            <label for="order" class="typecho-label">排序</label>
            <p><input id="order" name="order" type="text" value="<?php if($post->order) $post->order(); else _e(0);?>" class="w-100 text" /></p>
            <p class="description">文章排序，数字越大位置越靠前，不需要则不填，默认为0。</p>
        </section>
        <?php
    }

    public static function widget_content_post_edit_write($contents, $this_){
        $contents['order'] = $this_->request->get('order',0);
        return $contents;
    }

    // 设置输出 倒叙
    public static function handleArchiveQuery($this_, $select)
    {
        if ($this_->parameter->type == 'category' and $this_->parameter->order == 'order'){
            $select->order('table.contents.order', Typecho_Db::SORT_DESC);
        }

        Typecho_Db::get()->fetchAll($select, array($this_, 'push'));
    }

    /** handle初始化 */
    public static function handleArchivehandleInit($this_, $select)
    {

    }
    public static function handle($type,$archive,$select)
    {
        if ($type == 'navigation'){
            Widget_Archive_One::navigatorHandle($type,$archive,$select);
        }
//        return true;
    }


    /**
     * 修改跳转到管理链接
     * @param $contents
     * @param $this_
     */
    public static function handlePostPublishFinish($contents, $this_)
    {

        $order = $this_->request->get('order',null);
        $type_ = $this_->request->get('type',null);
        if ($type_ and $type_ == 'navigation'){
            // 修改文章类型为链接
            $con = $this_->request->from('type');
            $db = Typecho_Db::get();
            $db->query($db->sql()->where('cid = ?', $this_->cid)->update('table.contents')->rows($con));
        }
        /** 发送ping */
        $trackback = array_unique(preg_split("/(\r|\n|\r\n)/", trim($this_->request->trackback)));
        $this_->widget('Widget_Service')->sendPing($this_->cid, $trackback);

        /** 设置提示信息 */
        $this_->widget('Widget_Notice')->set('post' == $this_->type ?
            _t('文章 "<a href="%s">%s</a>" 已经发布', $this_->permalink, $this_->title) :
            _t('文章 "%s" 等待审核', $this_->title), 'success');

        /** 设置高亮 */
        $this_->widget('Widget_Notice')->highlight($this_->theId);

        /** 获取页面偏移 */
        $pageQuery = $this_->getPageOffsetQuery($this_->created);

        /** 页面跳转 */
        if ($type_ == 'navigation'){
            $this_->response->redirect(Typecho_Common::url('extending.php?panel=WebStack%2Fpages%2Fadmin%2Fmanage-navi.php&' . $pageQuery, Helper::options()->adminUrl));
        }else{
            $this_->response->redirect(Typecho_Common::url('manage-posts.php?' . $pageQuery, Helper::options()->adminUrl));
        }
    }

    /**
     * 友情链接
     * @return string
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     */
    public static function linksInstall()
    {
        $installDb = Typecho_Db::get();
        $type = explode('_', $installDb->getAdapterName());
        $type = array_pop($type);
        $prefix = $installDb->getPrefix();
        $scripts = file_get_contents('usr/plugins/WebStack/'.$type.'.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8', $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $installDb->query($script, Typecho_Db::WRITE);
                }
            }
            return '建立友情链接数据表，插件启用成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && (1050 == $code || '42S01' == $code)) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    $script = 'SELECT `lid`, `name`, `url`, `sort`, `image`, `description`, `user`, `order` from `' . $prefix . 'links`';
                    $installDb->query($script, Typecho_Db::READ);
                    return '检测到友情链接数据表，友情链接插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if(('Mysql' == $type && (1054 == $code || '42S22' == $code)) ||
                        ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                        return WebStack_Plugin::linksUpdate($installDb, $type, $prefix);
                    }
                    throw new Typecho_Plugin_Exception('数据表检测失败，友情链接插件启用失败。错误号：'.$code);
                }
            } else {
                throw new Typecho_Plugin_Exception('数据表建立失败，友情链接插件启用失败。错误号：'.$code);
            }
        }
    }

    public static function linksUpdate($installDb, $type, $prefix)
    {
        $scripts = file_get_contents('usr/plugins/WebStack/Update_'.$type.'.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8', $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $installDb->query($script, Typecho_Db::WRITE);
                }
            }
            return '检测到旧版本友情链接数据表，升级成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && (1060 == $code || '42S21' == $code))) {
                return '友情链接数据表已经存在，插件启用成功';
            }
            throw new Typecho_Plugin_Exception('友情链接插件启用失败。错误号：'.$code);
        }
    }

    public static function form($action = NULL)
    {
        /** 构建表格 */
        $options = Typecho_Widget::widget('Widget_Options');
        $form = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/webstack-action', $options->index),
            Typecho_Widget_Helper_Form::POST_METHOD);

        /** 链接名称 */
        $name = new Typecho_Widget_Helper_Form_Element_Text('name', NULL, NULL, _t('链接名称*'));
        $form->addInput($name);

        /** 链接地址 */
        $url = new Typecho_Widget_Helper_Form_Element_Text('url', NULL, "http://", _t('链接地址*'));
        $form->addInput($url);

        /** 链接分类 */
        $sort = new Typecho_Widget_Helper_Form_Element_Text('sort', NULL, NULL, _t('链接分类'), _t('建议以英文字母开头，只包含字母与数字'));
        $form->addInput($sort);

        /** 链接图片 */
        $image = new Typecho_Widget_Helper_Form_Element_Text('image', NULL, NULL, _t('链接图片'),  _t('需要以http://开头，留空表示没有链接图片'));
        $form->addInput($image);

        /** 链接描述 */
        $description =  new Typecho_Widget_Helper_Form_Element_Textarea('description', NULL, NULL, _t('链接描述'));
        $form->addInput($description);

        /** 自定义数据 */
        $user = new Typecho_Widget_Helper_Form_Element_Text('user', NULL, NULL, _t('自定义数据'), _t('该项用于用户自定义数据扩展'));
        $form->addInput($user);

        /** 链接动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        /** 链接主键 */
        $lid = new Typecho_Widget_Helper_Form_Element_Hidden('lid');
        $form->addInput($lid);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        $request = Typecho_Request::getInstance();

        if (isset($request->lid) && 'insert' != $action) {
            /** 更新模式 */
            $db = Typecho_Db::get();
            $prefix = $db->getPrefix();
            $link = $db->fetchRow($db->select()->from($prefix.'links')->where('lid = ?', $request->lid));
            if (!$link) {
                throw new Typecho_Widget_Exception(_t('链接不存在'), 404);
            }

            $name->value($link['name']);
            $url->value($link['url']);
            $sort->value($link['sort']);
            $image->value($link['image']);
            $description->value($link['description']);
            $user->value($link['user']);
            $do->value('update');
            $lid->value($link['lid']);
            $submit->value(_t('编辑链接'));
            $_action = 'update';
        } else {
            $do->value('insert');
            $submit->value(_t('增加链接'));
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** 给表单增加规则 */
        if ('insert' == $action || 'update' == $action) {
            $name->addRule('required', _t('必须填写链接名称'));
            $url->addRule('required', _t('必须填写链接地址'));
            $url->addRule('url', _t('不是一个合法的链接地址'));
            $image->addRule('url', _t('不是一个合法的图片地址'));
        }
        if ('update' == $action) {
            $lid->addRule('required', _t('链接主键不存在'));
            $lid->addRule(array(new WebStack_Plugin, 'LinkExists'), _t('链接不存在'));
        }
        return $form;
    }

    public static function LinkExists($lid)
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $link = $db->fetchRow($db->select()->from($prefix.'links')->where('lid = ?', $lid)->limit(1));
        return $link ? true : false;
    }

    /**
     * 控制输出格式
     */
    public static function output_str($pattern=NULL, $links_num=0, $sort=NULL)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        if (!isset($options->plugins['activated']['WebStack'])) {
            return '友情链接插件未激活';
        }
        if (!isset($pattern) || $pattern == "" || $pattern == NULL || $pattern == "SHOW_TEXT") {
            $pattern = "<li><a href=\"{url}\" title=\"{title}\" target=\"_blank\">{name}</a></li>\n";
        } else if ($pattern == "SHOW_IMG") {
            $pattern = "<li><a href=\"{url}\" title=\"{title}\" target=\"_blank\"><img src=\"{image}\" alt=\"{name}\" /></a></li>\n";
        } else if ($pattern == "SHOW_MIX") {
            $pattern = "<li><a href=\"{url}\" title=\"{title}\" target=\"_blank\"><img src=\"{image}\" alt=\"{name}\" /><span>{name}</span></a></li>\n";
        }
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $options = Typecho_Widget::widget('Widget_Options');
        $nopic_url = Typecho_Common::url('/usr/plugins/WebStack/assets/img/nopic.jpg', $options->siteUrl);
        $sql = $db->select()->from($prefix.'links');
        if (!isset($sort) || $sort == "") {
            $sort = NULL;
        }
        if ($sort) {
            $sql = $sql->where('sort=?', $sort);
        }
        $sql = $sql->order($prefix.'links.order', Typecho_Db::SORT_ASC);
        $links_num = intval($links_num);
        if ($links_num > 0) {
            $sql = $sql->limit($links_num);
        }
        $links = $db->fetchAll($sql);
        $str = "";
        foreach ($links as $link) {
            if ($link['image'] == NULL) {
                $link['image'] = $nopic_url;
            }
            $str .= str_replace(
                array('{lid}', '{name}', '{url}', '{sort}', '{title}', '{description}', '{image}', '{user}'),
                array($link['lid'], $link['name'], $link['url'], $link['sort'], $link['description'], $link['description'], $link['image'], $link['user']),
                $pattern
            );
        }
        return $str;
    }

    //输出
    public static function output($pattern=NULL, $links_num=0, $sort=NULL)
    {
        echo WebStack_Plugin::output_str($pattern, $links_num, $sort);
    }

    /**
     * 解析
     *
     * @access public
     * @param array $matches 解析值
     * @return string
     */
    public static function parseCallback($matches)
    {
        $db = Typecho_Db::get();
        $pattern = $matches[3];
        $links_num = $matches[1];
        $sort = $matches[2];
        return WebStack_Plugin::output_str($pattern, $links_num, $sort);
    }

    public static function parse($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;

        if ($widget instanceof Widget_Archive || $widget instanceof Widget_Abstract_Comments) {
            return preg_replace_callback("/<links\s*(\d*)\s*(\w*)>\s*(.*?)\s*<\/links>/is", array('WebStack_Plugin', 'parseCallback'), $text);
        } else {
            return $text;
        }
    }

    // 在文章页隐藏 链接的自定义字段
    public static function end_redefine(){
        $foot = '<script>$(document).ready(function() {
          $(".row.typecho-page-main.typecho-post-area #custom-field [id|=url]").parent().parent().parent().hide();
$(".row.typecho-page-main.typecho-post-area #custom-field [id|=text]").parent().parent().parent().hide();
$(".row.typecho-page-main.typecho-post-area #custom-field [id|=logo]").parent().parent().parent().hide();
        })</script>';
        echo $foot;
    }
}
