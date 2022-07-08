<?php

/**
 * mysite 页面
 * Class Widget_blog
 */
class Widget_mysite extends Widget_Archive
{
    public static function handle($archive, $select)
    {
        $archive->setArchiveType('mysite');
        //login required

        $select->where('table.contents.type = ?', 'navigation');
        $explore_categories = Helper::options()->explore_categories;
        $explore_categories = str_replace(" ", "", $explore_categories);
        $explore_categories_arr = explode("||", $explore_categories);
        foreach ($explore_categories_arr as $key => $val){
            $explore_categories_arr[$key] = trim($val);
        }
        if ($explore_categories and !empty($explore_categories_arr)){
            $select->where('table.contents.cid in ?',$explore_categories_arr)->where('table.contents.authorId = ?',$archive->user->authorId);
        }
        /** 仅输出文章 */
        $archive->setCountSql(clone $select);

        $select->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->page($archive->getCurrentPage(), $archive->parameter->pageSize);
        $archive->query($select);

        /** 设置关键词 */
        $archive->setKeywords("我的博客");

        /** 设置描述 */
        $archive->setDescription("我的博客");
        /** 设置标题 */
        $archive->setArchiveTitle("我的博客");

    }
}