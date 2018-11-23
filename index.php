<?php

require_once dirname(__FILE__) . "/Module/ContentsDatabaseManager.php";
require_once dirname(__FILE__) . "/Module/OutlineText.php";
require_once dirname(__FILE__) . "/Module/ContentsViewerUtil.php";
require_once dirname(__FILE__) . "/Module/Stopwatch.php";
require_once dirname(__FILE__) . "/Module/Debug.php";




OutlineText\Parser::Init();

$rootContentPath = ContentsDatabaseManager::DefalutRootContentPath();

$parentsMaxCount = 3;
$brotherTitleMaxStrWidth = 40;

$plainTextMode = false;

$warningMessages = [];


$contentPath =$rootContentPath;
if(isset($_GET['content']))
{
    $contentPath = urldecode($_GET['content']);
}


if(isset($_GET['plainText'])){
    $plainTextMode = true;
}

// if(isset($_GET['warning'])){
//     if($_GET['warning'] == 'old-url'){
//         $warningMessages[] = '古いURLでアクセスされました(現在のURLは最新です).<br>今後のアップデートでアクセス元のリンクが切れる可能性があります.';
//     }
// }


$currentContent = new Content();
$parents = [];
$children = [];
$leftContent = null;
$rightContent = null;
$htmlConvertTime = 0;
$pageBuildTime = 0;


$stopwatch = new Stopwatch();


// コンテンツの取得
$isGetCurrentContent = $currentContent->SetContent($contentPath);

$rootContentPath = ContentsDatabaseManager::GetRelatedRootFile($contentPath);
$metaFileName = ContentsDatabaseManager::GetRelatedMetaFileName($contentPath);

if($isGetCurrentContent && !$plainTextMode)
{
    // コンテンツの設定

    //echo $isOldURL ? "true" : "false";
    
    $context = null;

    $stopwatch->Start();
    // CurrentContentのSummaryとBodyをDecode
    $currentContent->SetSummary(OutlineText\Parser::Parse($currentContent->Summary(), $context));
    $currentContent->SetBody(OutlineText\Parser::Parse($currentContent->Body(), $context));

    $htmlConvertTime = $stopwatch->Elapsed();
    $stopwatch->Restart();

    // ChildContentsの取得
    $childrenPathList = $currentContent->ChildPathList();
    $childrenPathListCount = count($childrenPathList);
    for($i = 0; $i < $childrenPathListCount; $i++)
    {
        $child = $currentContent->Child($i);
        if($child !== false){
            $children[] = $child;
        }
    }

    // Parentsの取得
    $parent = $currentContent->Parent();

    for($i = 0; $i < $parentsMaxCount; $i++)
    {
        if($parent === false)
        {
            break;
        }
        $parents[] = $parent;
        $parent = $parent->Parent();
    }

    // echo count($parents);
    //LeftContent, RightContentの取得
    if(isset($parents[0]))
    {
        $parent = $parents[0];
        $brothers = $parent->ChildPathList();
        $myIndex = $currentContent->ChildIndex();

        if($myIndex >= 0)
        {
            if($myIndex > 0)
            {
                $leftContent = $parent->Child($myIndex - 1);
            }
            if($myIndex <  count($brothers) - 1)
            {
                $rightContent = $parent->Child($myIndex + 1);
            }
        }
    }


}

if(!$isGetCurrentContent){
    
    header("HTTP/1.1 404 Not Found");
}


if($plainTextMode && $isGetCurrentContent){
    echo "<!DOCTYPE html><html lang='ja'><head></head><body>";
    echo "<pre style='word-wrap: break-word; white-space: pre-wrap'>";
    echo htmlspecialchars(file_get_contents(Content::RealPath($contentPath)));
    echo "</pre>";
    echo "</body></html>";
    exit();
}


?>




<!DOCTYPE html>
<html lang="ja">

<head>
    <?php readfile("Client/Common/CommonHead.html"); ?>
    

    <link rel="shortcut icon" href="Client/Common/favicon.ico" type="image/vnd.microsoft.icon" />

    

    <!-- Code表記 -->
    <script type="text/javascript" src="Client/syntaxhighlighter/scripts/shCore.js"></script>
    <script type="text/javascript" src="Client/syntaxhighlighter/scripts/shBrushCpp.js"></script>
    <script type="text/javascript" src="Client/syntaxhighlighter/scripts/shBrushCSharp.js"></script>
    <script type="text/javascript" src="Client/syntaxhighlighter/scripts/shBrushXml.js"></script>
    <script type="text/javascript" src="Client/syntaxhighlighter/scripts/shBrushPhp.js"></script>
    <script type="text/javascript" src="Client/syntaxhighlighter/scripts/shBrushPython.js"></script>
    <script type="text/javascript" src="Client/syntaxhighlighter/scripts/shBrushJava.js"></script>
    <script type="text/javascript" src="Client/syntaxhighlighter/scripts/shBrushBash.js"></script>
    <link type="text/css" rel="stylesheet" href="Client/syntaxhighlighter/styles/shCoreDefault.css" />
    <script type="text/javascript">SyntaxHighlighter.all();</script>


    <!-- 数式表記 -->
    <script type="text/x-mathjax-config">
    MathJax.Hub.Config({ 
        tex2jax: { inlineMath: [['$','$'], ["\\(","\\)"]] },
        TeX: { equationNumbers: { autoNumber: "AMS" } }
    });
    </script>
    <script type="text/javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-AMS_CHTML">
    </script>
    <meta http-equiv="X-UA-Compatible" CONTENT="IE=EmulateIE7" />


    <link rel="stylesheet" href="Client/OutlineText/OutlineTextStandardStyle.css" />
    <link rel="stylesheet" href="Client/ContentsViewer/ContentsViewerStandard.css" />
    <script type="text/javascript" src="Client/ContentsViewer/ContentsViewerStandard.js"></script>




    <?php

    if($isGetCurrentContent){
    
        //title作成
        $title = "";
        $title .= $currentContent->Title();
        if(isset($parents[0]))
        {
            $title .=" | " . $parents[0]->Title();
        }

        echo "<title>".$title."</title>";
    }
    else{
        
        echo "<title>Not Found...</title>";
    }

    ?>

</head>

<body>

    <div id="header-area">
        <a href="<?=CreateContentHREF($rootContentPath)?>">ContentsViewer</a>
    </div>

    

    <?php


    //CurrentContentを取得したかどうか
    if(!$isGetCurrentContent)
    {
        $isFatalError = true;
        ?>
        <div id="error-message-box">
        <h1>Not Found...</h1> <br/>
        存在しない or 移動した コンテンツにアクセスした可能性があります.<br/>
        <a href="<?=CreateContentHREF($rootContentPath)?>">TopPageから探す</a>
        <div class='note'>
            * 品質向上のためこの問題は管理者に報告されます.
        </div>
        </div>

        <?php
        
        Debug::LogError("Not found page Accessed:\n  Content Path: {$contentPath}");
        exit;
    }

    $titleField = CreateTitleField($currentContent, $parents); 

    ?>
    <div id="print-title">
        <?=$titleField?>
    </div>
    <?php


    // === Navigator作成 =============================================
    $navigator = "";
    $navigator.= "<div class='navi'>";
    $navigator .= "<ul>";
    CreateNavHelper($parents, count($parents)- 1, $currentContent, $children, $navigator);
    $navigator .= "</ul>";
    $navigator.= "</div>";


    // === Left Side Area ============================================
    ?>
    <div id ='left-side-area'>
        <?=$navigator?>
    </div>

    <?php

    // === Right Side Area ===========================================
    ?>
    <div id = 'right-side-area'>
        Index
        <div class='navi'></div>
        <a href='<?=CreateHREFForPlainTextMode()?>'>このページのソースコードを表示</a>
    </div>
    <?php

    // === Main Area =================================================
    ?>
    <div id="main-area">
    
    <?php
    
    // 最終更新欄
    ?>
    <div class="file-date-field">
        <img src='Client/Common/CreatedAtStampA.png' alt='公開日'>: <?=$currentContent->CreatedAt()?>
        <img src='Client/Common/UpdatedAtStampA.png' alt='更新日'>: <?=$currentContent->UpdatedAt()?>
    </div>

    <?php
    echo "<ul class='tag-links'>";
    //echo $currentContent->Tags()[0];
    foreach($currentContent->Tags() as $name){
        echo "<li><a href='" . CreateTagDetailHREF($name, $metaFileName) . "'>" . $name . "</a></li>";
    }
    echo "</ul>";


    // 概要欄
    echo '<div id="summary-field" class="summary">';
    echo $currentContent->Summary();

    if($currentContent->IsRoot()){
        ContentsDatabaseManager::LoadRelatedTagMap($contentPath);
        $tagMap = Content::GlobalTagMap();
        echo CreateNewBox($tagMap);

        
        echo "<h2>タグ一覧</h2>";
        echo CreateTagListElement($tagMap, $metaFileName);
    }
    echo '</div>';
    
    // 目次欄(小画面で表示される)
    ?>
    <div id = 'index-area-on-small-screen'>
        Index
    </div>
    <?php

    //本編
    ?>
    <div id="main-content-field" class="main-content">
        <?=$currentContent->Body()?>
    </div>
    <?php

    //子コンテンツ
    echo '<div id="children-field">';
    $childrenCount = count($children);
    for($i = 0; $i < $childrenCount; $i++)
    {
        ?>
        <div style='width:100%; display: table'>

            <div style='display: table-cell'>
                <a class="link-block-button" href ="<?=CreateContentHREF($children[$i]->Path())?>">
                    <?=$children[$i]->Title()?>
                </a>
            </div>
        <?php
        ////B-----
        //echo "<div class='ChildDetailButton' style='display:table-cell;  width:10%;' "
        //.'onmouseover="QuickLookMouse('
        //."'ChildContent"
        //.$i
        //."')"
        //.'" '
        //.'ontouchstart="QuickLookTouch('
        //."'ChildContent"
        //.$i
        //."')"
        //.'" '
        //.'onmouseout="ExitQuickLookMouse()" '
        //.'ontouchend="ExitQuickLookTouch()"'
        //."></div>";
        ////---

        ////C-----
        //echo "<div class='ContentContainer' "
        //."id='ChildContent".$i."Container"."'"
        //.">";
        //echo "<h1>" . $children[$i]->GetTitle(). "</h1>";
        //echo "<div>" . $children[$i]->GetAbstract(). "</div>";
        //echo "<div>" . $children[$i]->GetRootContent(). "</div>";
        //echo "</div>";
        ////---
        ?>
        </div>
        <?php
    }
    echo '</div>';

    
    ?>
    </div>
    <?php
    // End Main Area =============



    // --- Bottom Of MainArea On Small Screen ------------------------
    ?>
    <div id='bottom-of-main-area-on-small-screen'>
        <a href='<?=CreateHREFForPlainTextMode()?>'>このページのソースコードを表示</a>
        <?=$navigator?>
    </div>

    <?php
    // === Top Area ==================================================
    ?>
    
    <div id="top-area">
        <?=$titleField?>
    </div>

    <?php

    // === Right Brother Area ========================================
    //echo $myIndex;
    if(!is_null($rightContent))
    {

        if($rightContent !== false)
        {
            echo '<a id="right-brother-area"  href ="'.CreateContentHREF($rightContent->Path()).'">';
            echo  mb_strimwidth($rightContent->Title(), 0, $brotherTitleMaxStrWidth, "...", "UTF-8") . " &gt;";
            echo '</a>';
            //echo "<div id = 'RightContentContainer' class='ContentContainer'>";
            //echo "<h1>" . $rightContent->GetTitle(). "</h1>";
            //echo "<div>" . $rightContent->GetAbstract(). "</div>";
            //echo "<div>" . $rightContent->GetRootContent(). "</div>";
            //echo "</div>";
        }
    }

    // === Left Brother Area ========================================
    if(!is_null($leftContent))
    {

        if($leftContent !== false)
        {
            echo '<a id="left-brother-area" href ="'.CreateContentHREF($leftContent->Path()).'">';
            echo  "&lt; ". mb_strimwidth($leftContent->Title(), 0, $brotherTitleMaxStrWidth, "...", "UTF-8");
            echo '</a>';
            //echo "<div id = 'LeftContentContainer' class='ContentContainer'>";
            //echo "<h1>" . $leftContent->GetTitle(). "</h1>";
            //echo "<div>" . $leftContent->GetAbstract(). "</div>";
            //echo "<div>" . $leftContent->GetRootContent(). "</div>";
            //echo "</div>";
        }
    }



    $stopwatch->Stop();
    $pageBuildTime = $stopwatch->Elapsed();

    ?>

    <div id='footer'>
        <a href='./login.php'>Manage</a>    <a href='./content-editor.php?content=<?=$currentContent->Path()?>'>Edit</a><br/>
        <b>ConMAS 2018.</b> HTML Convert Time: <?=sprintf("%.2f[ms]", $htmlConvertTime*1000);?>; Page Build Time: <?=sprintf("%.2f[ms]", $pageBuildTime*1000);?>
    </div>

    <?php

    // $warningMessages[] = "現在メンテナンス中です...";

    if($htmlConvertTime + $pageBuildTime > 1.0){
        Debug::LogWarning("Performance Note:\n  HtmlConverTime: $htmlConvertTime;\n  PageBuildTime: $pageBuildTime;\n  Page Title: {$currentContent->Title()};\n  Page Path: {$currentContent->Path()}");
        $warningMessages[] = "申し訳ございません m(. .)m<br> ページの生成に時間がかかったようです.<br>品質向上のためこの問題は管理者に報告されます.";
    }
    

    if(count($warningMessages) !== 0){
    ?>

    <div id='warning-message-box'>
        <ul>
        <?php
        foreach ($warningMessages as $message){
            echo '<li>' . $message . '</li>';
        }
        ?>
        </ul>
    </div>

    <?php
    }
    ?>

</body>
</html>

<?php


function CreateTitleField($currentContent, $parents){
    $field = '<div class="title-field">';

    //親コンテンツ
    $field .= '<ul class="breadcrumb">';

    $parentsCount = count($parents);
    for($i = 0; $i < $parentsCount; $i++)
    {
        $index = $parentsCount - $i - 1;

        if($parents[$index] === false)
        {
            $field .= '<li>Error; 存在しないコンテンツです</li>';
        }
        else
        {
            $field .= '<li itemscope="itemscope" itemtype="http://data-vocabulary.org/Breadcrumb">';
            $field .= '<a  href ="'.CreateContentHREF($parents[$index]->Path()).'" itemprop="url">';
            $field .= '<span itemprop="title">' . $parents[$index]->Title() . '</span></a></li>';
        }
    }
    $field .= '</ul>';

    //タイトル欄
    $field .= '<h1 class="title">' . $currentContent->Title() . '</h1>';

    $field .= '</div>';
    return $field;
}

function CreateHREFForPlainTextMode(){
    $query = $_SERVER["QUERY_STRING"] . "&plainText";

    return "?" . $query;
}


function CreateNavHelper($parents, $parentsIndex, $currentContent, $children,  &$navigator){
    
    if($parentsIndex < 0){
        // echo '1+';
        $navigator .=  '<li><a class = "selected" href="' . CreateContentHREF($currentContent->Path()) . '">' . $currentContent->Title() . '</a></li>';

        $navigator .= "<ul>";
        foreach($children as $c){

            $navigator .=  '<li><a href="' . CreateContentHREF($c->Path()) . '">' . $c->Title() . '</a></li>';
        }

        $navigator.="</ul>";

        return;
    }

    $childrenCount = $parents[$parentsIndex]->ChildCount();

    $navigator .=  '<li><a class = "selected" href="' . CreateContentHREF($parents[$parentsIndex]->Path()) . '">' . $parents[$parentsIndex]->Title() . '</a></li>';

    $navigator .=  "<ul>";
    if($parentsIndex == 0){
        // echo '2+';
        $currentContentIndex = $currentContent->ChildIndex();
        for($i = 0; $i < $childrenCount; $i++){

            $child = $parents[$parentsIndex]->Child($i);
            if($child === false){
                continue;
            }

            if($i == $currentContentIndex){
                $navigator .=  '<li><a class = "selected" href="' . CreateContentHREF($child->Path()) . '">' . $child->Title() . '</a></li>';

                $navigator .= "<ul>";
                foreach($children as $c){
                    $navigator .=  '<li><a href="' . CreateContentHREF($c->Path()) . '">' . $c->Title() . '</a></li>';
                }
                $navigator.="</ul>";
            }
            else{
                $navigator .=  '<li><a href="' . CreateContentHREF($child->Path()) . '">' . $child->Title() . '</a></li>';
            }
        }
    }
    else{
        // echo '3+';
        $nextParentIndex = $parents[$parentsIndex - 1]->ChildIndex();
        for($i = 0; $i < $childrenCount; $i++){
            if($i == $nextParentIndex){
                CreateNavHelper($parents, $parentsIndex-1, $currentContent, $children, $navigator);
            }
            else{
                $child = $parents[$parentsIndex]->Child($i);
                if($child === false){
                    continue;
                }
                $navigator .=  '<li><a href="' . CreateContentHREF($child->Path()) . '">' . $child->Title() . '</a></li>';
            }
        }
    }
    $navigator.=  "</ul>";
    return;
}
?>