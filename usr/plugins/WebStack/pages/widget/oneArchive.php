<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: Posts.php 200 2008-05-21 06:33:20Z magike.net $
 */

/**
 * 内容的文章基类
 * 定义的css类
 * p.more:阅读全文链接所属段落
 *
 * @package Widget
 */
class Widget_Archive_One extends Widget_Abstract_Contents
{
   public static function navigatorHandle($type,$archive,$select){
       /** 如果是分类 */
       $categorySelect = $archive->db->select()
           ->from('table.metas')
           ->where('type = ?', 'category')
           ->limit(1);

       if (isset($archive->request->mid)) {
           $categorySelect->where('mid = ?', $archive->request->filter('int')->mid);
       }

       if (isset($archive->request->slug)) {
           $categorySelect->where('slug = ?', $archive->request->slug);
       }

       if (isset($archive->request->directory)) {
           $directory = explode('/', $archive->request->directory);
           $categorySelect->where('slug = ?', $directory[count($directory) - 1]);
       }

       $category = $archive->db->fetchRow($categorySelect);
       if (empty($category)) {
           throw new Typecho_Widget_Exception(_t('分类不存在'), 404);
       }

       $categoryListWidget = $archive->widget('Widget_Metas_Category_List', 'current=' . $category['mid']);
       $category = $categoryListWidget->filter($category);

       if (isset($directory) && ($archive->request->directory != implode('/', $category['directory']))) {
           throw new Typecho_Widget_Exception(_t('父级分类不存在'), 404);
       }

       $children = $categoryListWidget->getAllChildren($category['mid']);
       $children[] = $category['mid'];

       /** fix sql92 by 70 */
       $select->join('table.relationships', 'table.contents.cid = table.relationships.cid')
           ->where('table.relationships.mid IN ?', $children)
           ->where('table.contents.type = ?', 'navigation')
           ->group('table.contents.cid');

       /** 设置分页 */
       $archive->setPageRow(array_merge($category, array(
           'slug'          =>  urlencode($category['slug']),
           'directory'     =>  implode('/', array_map('urlencode', $category['directory']))
       )));

       /** 设置关键词 */
       $archive->setKeywords($category['name']);

       /** 设置描述 */
       $archive->setDescription($category['description']);

       /** 设置头部feed */
       /** RSS 2.0 */
       $archive->setFeedUrl($category['feedUrl']);

       /** RSS 1.0 */
       $archive->setFeedRssUrl($category['feedRssUrl']);

       /** ATOM 1.0 */
       $archive->setFeedAtomUrl($category['feedAtomUrl']);

       /** 设置标题 */
       $archive->setArchiveTitle($category['name']);

       /** 设置归档类型 */
       $archive->setArchiveType('category');

       /** 设置归档缩略名 */
       $archive->setArchiveSlug($category['slug']);

   }
}

