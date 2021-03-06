﻿{function name=render_main_menu level=1 parent=0}
          <ul {if $level eq 1}class="nav navbar-nav"{else}class="dropdown-menu lv{$level}" aria-labelledby="drop{$parent}"{/if}>
{foreach $items as $item}{if count($item.child) > 0}{if $level eq 1}
            <li class="dropdown{if $item.active} active{/if}">
              <a href="{$item.link}" data-toggle="dropdown" class="dropdown-toggle" role="button" id="drop{$item.id}">
                {$item.title} <b class="caret"></b>
              </a>
{else}
            <li class="dropdown-submenu{if $item.active} active{/if}">
              <a href="{$item.link}" data-toggle="dropdown" class="dropdown-toggle" role="button" id="drop{$item.id}">
                {$item.title}
              </a>
{/if}
{if count($item.child) > 0}{call name=render_main_menu items=$item.child level=$level+1 parent=$item.id}{/if}
            </li>
{else}
            <li{if $item.active} class="active"{/if}>
              <a href="{$item.link}"{if $item.is_url} target="_blank"{/if}>{$item.title}{if $item.is_url} <i class="fa fa-exterrnal-link"></i>{/if}</a>
            </li>
{/if}{/foreach}
          </ul>
{/function}

<!-- Navigation -->
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
  <div class="container">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="fa fa-bars"></span>
      </button>
      <a class="navbar-brand" href="/">{$synWebsiteTitle}</a>
    </div>
    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      {submenu startPage=22 includeParent=true expand=true}
      {call render_main_menu items=$submenu}


      <ul class="nav navbar-nav navbar-right">
      {userMenu}{if $user_button neq ''}
        <li class="dropdown">
        {$user_button}
        </li>
      {/if}
      {lang}{if $langlist neq ''}
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" role="button" data-toggle="dropdown">
            <i class="fa fa-globe"></i> {$active_lang.initial|upper} <i class="fa fa-angle-down"></i>
          </a>
          <ul class="dropdown-menu">
          {foreach $langlist as $lang}
            <li{if $lang.active} class="active"{/if}>
              <a href="{$lang.path}">
                <img src="{$lang.flag}" alt="{$lang.initial}"> {$lang.name|ucwords}
              </a>
            </li>
          {/foreach}
          </ul>
        </li>
      {/if}
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container -->
</nav>
