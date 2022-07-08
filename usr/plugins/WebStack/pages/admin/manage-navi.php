<?php
include 'common.php';
include 'header.php';
include 'menu.php';
require_once 'WebStack/pages/widget/Widget_Contents_Post_Admin_Redefine.php';

$stat = Typecho_Widget::widget('Widget_Stat');
$posts = Typecho_Widget::widget('Widget_Contents_Post_Admin_Redefine');
if ($request){
    $isAllPosts = ('on' == $request->get('__typecho_all_posts') || 'on' == Typecho_Cookie::get('__typecho_all_posts'));
}else{
    $isAllPosts = ('on' == Typecho_Cookie::get('__typecho_all_posts'));

}
define('MNAVI','extending.php?panel=WebStack%2Fpages%2Fadmin%2Fmanage-navi.php&');
define('WNAVI','http://typecho/admin/extending.php?panel=WebStack%2Fpages%2Fadmin%2Fwrite-navi.php&');
?>
<style>
    .link-name{
    }
</style>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 typecho-list">
                <div class="clearfix">
                    <ul class="typecho-option-tabs right">
                    <?php if($user && $user->pass('editor', true) && !isset($request->uid)): ?>
                        <li class="<?php if($isAllPosts): ?> current<?php endif; ?>"><a href="<?php echo $request->makeUriByRequest('__typecho_all_posts=on'); ?>"><?php _e('所有'); ?></a></li>
                        <li class="<?php if(!$isAllPosts): ?> current<?php endif; ?>"><a href="<?php echo $request->makeUriByRequest('__typecho_all_posts=off'); ?>"><?php _e('我的'); ?></a></li>
                    <?php endif; ?>
                    </ul>
                    <ul class="typecho-option-tabs">
                        <li<?php if(!isset($request->status) || 'all' == $request->get('status')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('manage-posts.php'
                        . (isset($request->uid) ? '?uid=' . $request->uid : '')); ?>"><?php _e('可用'); ?></a></li>
                        <li<?php if('waiting' == $request->get('status')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl(MNAVI.'status=waiting'
                        . (isset($request->uid) ? '&uid=' . $request->uid : '')); ?>"><?php _e('待审核'); ?>
                        <?php if(!$isAllPosts && $stat->myWaitingPostsNum > 0 && !isset($request->uid)): ?>
                            <span class="balloon"><?php $stat->myWaitingPostsNum(); ?></span>
                        <?php elseif($isAllPosts && $stat->waitingPostsNum > 0 && !isset($request->uid)): ?>
                            <span class="balloon"><?php $stat->waitingPostsNum(); ?></span>
                        <?php elseif(isset($request->uid) && $stat->currentWaitingPostsNum > 0): ?>
                            <span class="balloon"><?php $stat->currentWaitingPostsNum(); ?></span>
                        <?php endif; ?>
                        </a></li>
                        <li<?php if('draft' == $request->get('status')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl(MNAVI.'status=draft'
                        . (isset($request->uid) ? '&uid=' . $request->uid : '')); ?>"><?php _e('草稿'); ?>
                        <?php if(!$isAllPosts && $stat->myDraftPostsNum > 0 && !isset($request->uid)): ?>
                            <span class="balloon"><?php $stat->myDraftPostsNum(); ?></span>
                        <?php elseif($isAllPosts && $stat->draftPostsNum > 0 && !isset($request->uid)): ?>
                            <span class="balloon"><?php $stat->draftPostsNum(); ?></span>
                        <?php elseif(isset($request->uid) && $stat->currentDraftPostsNum > 0): ?>
                            <span class="balloon"><?php $stat->currentDraftPostsNum(); ?></span>
                        <?php endif; ?>
                        </a></li>
                    </ul>
                </div>

                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <input type="hidden" name="panel" value="WebStack/pages/admin/manage-navi.php"/>
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些导航链接吗?'); ?>" href="<?php if ($security) $security->index('/action/contents-post-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="search" role="search">
                            <?php if ('' != $request->keywords || '' != $request->category): ?>
                            <a href="<?php $options->adminUrl('extending.php?panel=WebStack/pages/admin/manage-navi.php'
                                . (isset($request->status) || isset($request->uid) ? '?' .
                                    (isset($request->status) ? 'status=' . htmlspecialchars($request->get('status')) : '') .
                                    (isset($request->uid) ? '?uid=' . htmlspecialchars($request->get('uid')) : '') : '')); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                            <?php endif; ?>
                            <input type="text" class="text-s" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($request->keywords); ?>" name="keywords" />
                            <select name="category">
                            	<option value=""><?php _e('所有分类'); ?></option>
                            	<?php Typecho_Widget::widget('Widget_Metas_Category_List')->to($category); ?>
                            	<?php while($category->next()): ?>
                            	<option value="<?php $category->mid(); ?>"<?php if($request->get('category') == $category->mid): ?> selected="true"<?php endif; ?>><?php $category->name(); ?></option>
                            	<?php endwhile; ?>
                            </select>
                            <button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
                            <?php if(isset($request->uid)): ?>
                            <input type="hidden" value="<?php echo htmlspecialchars($request->get('uid')); ?>" name="uid" />
                            <?php endif; ?>
                            <?php if(isset($request->status)): ?>
                                <input type="hidden" value="<?php echo htmlspecialchars($request->get('status')); ?>" name="status" />
                            <?php endif; ?>
                        </div>
                    </form>
                </div><!-- end .typecho-list-operate -->

                <form method="post" name="manage_posts" class="operate-form">
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">

                        <thead>
                            <tr>
                                <th> </th>
                                <th><?php _e('排序'); ?></th>
                                <th class="link-name"><?php _e('链接名'); ?></th>
                                <th><?php _e('链接'); ?></th>
                                <th><?php _e('作者'); ?></th>
                                <th><?php _e('分类'); ?></th>
                                <th><?php _e('日期'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php if($posts->have()): ?>
                            <?php while($posts->next()): ?>
                            <tr id="<?php $posts->theId(); ?>">
                                <td><input type="checkbox" value="<?php $posts->cid(); ?>" name="cid[]"/></td>
                                <td><?php $posts->order(); ?></td>
                                <td class="link-name">
                                <a href="<?php $options->adminUrl('extending.php?panel=WebStack%2Fpages%2Fadmin%2Fwrite-navi.php&cid=' . $posts->cid); ?>"><?php $posts->title(); ?></a>
                                <?php
                                if ($posts->hasSaved || 'post_draft' == $posts->type) {
                                    echo '<em class="status">' . _t('草稿') . '</em>';
                                } else if ('hidden' == $posts->status) {
                                    echo '<em class="status">' . _t('隐藏') . '</em>';
                                } else if ('waiting' == $posts->status) {
                                    echo '<em class="status">' . _t('待审核') . '</em>';
                                } else if ('private' == $posts->status) {
                                    echo '<em class="status">' . _t('私密') . '</em>';
                                } else if ($posts->password) {
                                    echo '<em class="status">' . _t('密码保护') . '</em>';
                                }
                                ?>
                                <a href="<?php $options->adminUrl('extending.php?panel=WebStack%2Fpages%2Fadmin%2Fwrite-navi.php&cid=' . $posts->cid); ?>" title="<?php _e('编辑 %s', htmlspecialchars($posts->title)); ?>"><i class="i-edit"></i></a>
                                <?php if ('post_draft' != $posts->type): ?>
                                <a href="<?php $options->adminUrl('extending.php?panel=WebStack%2Fpages%2Fadmin%2Fwrite-navi.php&cid=' . $posts->cid); ?>" title="<?php _e('浏览 %s', htmlspecialchars($posts->title)); ?>"><i class="i-exlink"></i></a>
                                <?php endif; ?>
                                </td>
                                <td style=""><a href="<?php echo $posts->fields->url?>"><?php echo $posts->fields->url?></a></td>
                                <td><a href="<?php $options->adminUrl('manage-posts.php?uid=' . $posts->author->uid); ?>"><?php $posts->author(); ?></a></td>
                                <td><?php $categories = $posts->categories; $length = count($categories); ?>
                                <?php foreach ($categories as $key => $val): ?>
                                    <?php echo '<a href="';
                                    $options->adminUrl('manage-posts.php?category=' . $val['mid']
                                    . (isset($request->uid) ? '&uid=' . $request->uid : '')
                                    . (isset($request->status) ? '&status=' . $request->status : ''));
                                    echo '">' . $val['name'] . '</a>' . ($key < $length - 1 ? ', ' : ''); ?>
                                <?php endforeach; ?>
                                </td>
                                <td>
                                <?php if ($posts->hasSaved): ?>
                                <span class="description">
                                <?php $modifyDate = new Typecho_Date($posts->modified); ?>
                                <?php _e('保存于 %s', $modifyDate->word()); ?>
                                </span>
                                <?php else: ?>
                                <?php $posts->dateWord(); ?>
                                <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                            	<td colspan="6"><h6 class="typecho-list-table-title"><?php _e('没有任何导航链接'); ?></h6></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </form><!-- end .operate-form -->

                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些导航链接吗?'); ?>" href="<?php $security->index('/action/contents-post-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                                </ul>
                            </div>
                        </div>

                        <?php if($posts->have()): ?>
                        <ul class="typecho-pager">
                            <?php $posts->pageNav(); ?>
                        </ul>
                        <?php endif; ?>
                    </form>
                </div><!-- end .typecho-list-operate -->
            </div><!-- end .typecho-list -->
        </div><!-- end .typecho-page-main -->
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
include 'footer.php';
?>
