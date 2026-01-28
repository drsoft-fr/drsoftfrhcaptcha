{**
 * @author Dylan Ramos - drSoft.fr
 * @copyright 2026 drSoft.fr
 * @license MIT
 *}

{if !isset($module)}
    {assign var='module' value=Module::getInstanceByName('drsoftfrhcaptcha')}
{/if}

<div class="panel mt-3">
    <div class="panel-wrapper">
        <div>
            <p class="my-0 text-center">
                {l s='Module: ' d='Modules.Drsoftfrhcaptcha.Admin'} {$module->displayName|default:''} -
                v{$module->version}
                - {l s='Author:' d='Modules.Drsoftfrhcaptcha.Admin'} {$module->author|default:''} - <a
                        href="mailto:{$module->authorEmail|default:''}"
                        target="_blank"
                        rel="noopener noreferrer">{$module->authorEmail|default:''}</a> - <a
                        href="{$module->moduleGithubRepositoryUrl|default:''}"
                        target="_blank"
                        rel="noopener noreferrer">{l s='GitHub repository' d='Modules.Drsoftfrhcaptcha.Admin'}</a>
                -
                <a href="{$module->moduleGithubIssuesUrl|default:''}"
                   target="_blank"
                   rel="noopener noreferrer">{l s='Issues URL' d='Modules.Drsoftfrhcaptcha.Admin'}</a>
            </p>
        </div>
        <div class="text-center mt-3">
            <a href="https://www.drsoft.fr"
               target="_blank" rel="noopener noreferrer"><img
                        alt="drSoft.fr" height="44" loading="lazy"
                        src="{$logo|default:''}" width="128"></a>
        </div>
    </div>
</div>