<?php

/**
 * WebStack_Action
 * @author gogobody
 * Class WebStack_Action
 */
class WebStack_Action extends Typecho_Widget implements Widget_Interface_Do
{
	private $db;
	private $options;
	private $prefix;
			
	public function insertLink()
	{
		if (WebStack_Plugin::form('insert')->validate()) {
			$this->response->goBack();
		}
		/** 取出数据 */
		$link = $this->request->from('name', 'url', 'sort', 'image', 'description', 'user');
		$link['order'] = $this->db->fetchObject($this->db->select(array('MAX(order)' => 'maxOrder'))->from($this->prefix.'links'))->maxOrder + 1;

		/** 插入数据 */
		$link['lid'] = $this->db->query($this->db->insert($this->prefix.'links')->rows($link));

		/** 设置高亮 */
		$this->widget('Widget_Notice')->highlight('link-'.$link['lid']);

		/** 提示信息 */
		$this->widget('Widget_Notice')->set(_t('链接 <a href="%s">%s</a> 已经被增加',
		$link['url'], $link['name']), NULL, 'success');

		/** 转向原页 */
		$this->response->redirect(Typecho_Common::url('extending.php?panel=WebStack%2Fpages%2Fadmin%2Fmanage-links.php', $this->options->adminUrl));
	}

	public function addHannysBlog()
	{
		/** 取出数据 */
		$link = array(
			'name' => "Hanny's Blog",
			'url' => "http://www.imhan.com", 
			'description' => "寒泥 - Typecho插件开发者", 
		);
		$link['order'] = $this->db->fetchObject($this->db->select(array('MAX(order)' => 'maxOrder'))->from($this->prefix.'links'))->maxOrder + 1;

		/** 插入数据 */
		$link['lid'] = $this->db->query($this->db->insert($this->prefix.'links')->rows($link));

		/** 设置高亮 */
		$this->widget('Widget_Notice')->highlight('link-'.$link['lid']);

		/** 提示信息 */
		$this->widget('Widget_Notice')->set(_t('链接 <a href="%s">%s</a> 已经被增加',
		$link['url'], $link['name']), NULL, 'success');

		/** 转向原页 */
		$this->response->redirect(Typecho_Common::url('extending.php?panel=WebStack%2Fpages%2Fadmin%2Fmanage-links.php', $this->options->adminUrl));
	}

	public function updateLink()
	{
		if (WebStack_Plugin::form('update')->validate()) {
			$this->response->goBack();
		}

		/** 取出数据 */
		$link = $this->request->from('lid', 'name', 'sort', 'image', 'url', 'description', 'user');

		/** 更新数据 */
		$this->db->query($this->db->update($this->prefix.'links')->rows($link)->where('lid = ?', $link['lid']));

		/** 设置高亮 */
		$this->widget('Widget_Notice')->highlight('link-'.$link['lid']);

		/** 提示信息 */
		$this->widget('Widget_Notice')->set(_t('链接 <a href="%s">%s</a> 已经被更新',
		$link['url'], $link['name']), NULL, 'success');

		/** 转向原页 */
		$this->response->redirect(Typecho_Common::url('extending.php?panel=WebStack%2Fpages%2Fadmin%2Fmanage-links.php', $this->options->adminUrl));
	}

    public function deleteLink()
    {
        $lids = $this->request->filter('int')->getArray('lid');
        $deleteCount = 0;
        if ($lids && is_array($lids)) {
            foreach ($lids as $lid) {
                if ($this->db->query($this->db->delete($this->prefix.'links')->where('lid = ?', $lid))) {
                    $deleteCount ++;
                }
            }
        }
        /** 提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('链接已经删除') : _t('没有链接被删除'), NULL,
        $deleteCount > 0 ? 'success' : 'notice');
        
        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=WebStack%2Fpages%2Fadmin%2Fmanage-links.php', $this->options->adminUrl));
    }

    public function sortLink()
    {
        $links = $this->request->filter('int')->getArray('lid');
        if ($links && is_array($links)) {
			foreach ($links as $sort => $lid) {
				$this->db->query($this->db->update($this->prefix.'links')->rows(array('order' => $sort + 1))->where('lid = ?', $lid));
			}
        }
    }

	public function action()
	{
        /**
         * normal action
         */
        $type = $this->request->get("type",null);
        $this->db = Typecho_Db::get();

        if ($type){
            switch ($type){
                case "posts":
                    if ($this->request->isPost()){
                        $mid = $this->request->get("mid");
                        if (!$mid){
                            $this->response->throwJson(array(
                                "msg" => "no mid",
                                "status" => 0,
                                "data" => ""
                            ));
                        }
                        $size = $this->request->get("size",1); // 默认一条
                        $page = $this->request->get("page",1);

                        $select = $this->db->select()->from("table.contents")->join('table.relationships', 'table.contents.cid = table.relationships.cid')
                            ->where('table.relationships.mid = ?', $mid)
                            ->where('table.contents.type = ?', 'post')
                            ->page($page,$size);
                        $arr = $this->db->fetchAll($select);
                        $data = array();
                        // 遍历分类下的文章
                        foreach ($arr as $key => $val){
                            $cid = $val["cid"];
                            $fields = $this->db->fetchAll($this->db->select("name","str_value")->from("table.fields")->where("cid = ?",$cid));
                            // 遍历文章对应的属性
                            $pics = array();$relatedSites = "";
                            foreach ($fields as $fkey => $fval){
                                if ($fval["name"] == "pic1"){
                                    array_push($pics,$fval["str_value"]);
                                }elseif ($fval["name"] == "pic2"){
                                    array_push($pics,$fval["str_value"]);
                                }elseif ($fval["name"] == "pic3"){
                                    array_push($pics,$fval["str_value"]);
                                }elseif ($fval["name"] == "relatedSites"){
                                    $relatedSites = $fval["str_value"];
                                }
                            }
                            // explode $relatedSites
                            if (empty($relatedSites)){
                                $relatedSites_arr= [];
                            }else{
                                $relatedSites_arr = explode("\r\n",$relatedSites);
                            }
                            $linklist = [];
                            $long = count($relatedSites_arr);
                            for ($i = 0;$i < $long;$i++){
                                array_push($linklist,explode("||", $relatedSites_arr[$i]));
                            }

                            array_push($data,array(
                                "cid" => $cid,
                                "title" => $val["title"],
                                "create" => $val["created"],
                                "text" => $val["text"],
                                "pics" => $pics,
                                "relatedSites" => $linklist
                            ));
                        }
                        $this->response->throwJson(array(
                            "msg" => "",
                            "status" => 1,
                            "data" => $data
                        ));
                    }

                    break;
            }
            $this->response->throwJson(array(
                "msg" => "unknow methods",
                "status" => 0,
                "data" => ""
            ));
            return;
        }
        /**
         * link action
         */
		$user = Typecho_Widget::widget('Widget_User');
		$user->pass('administrator');
		$this->prefix = $this->db->getPrefix();
		$this->options = Typecho_Widget::widget('Widget_Options');
		$this->on($this->request->is('do=insert'))->insertLink();
		$this->on($this->request->is('do=addhanny'))->addHannysBlog();
		$this->on($this->request->is('do=update'))->updateLink();
		$this->on($this->request->is('do=delete'))->deleteLink();
		$this->on($this->request->is('do=sort'))->sortLink();
		$this->response->redirect($this->options->adminUrl);
	}
}
