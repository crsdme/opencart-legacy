<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* design/seo_url_list.twig */
class __TwigTemplate_40f92d137fb9be083073c92b75a49a35b24c14a6ca2d67de853402659a9fb240 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo ($context["header"] ?? null);
        echo ($context["column_left"] ?? null);
        echo "
<div id=\"content\">
  <div class=\"page-header\">
    <div class=\"container-fluid\">
      <div class=\"pull-right\">
        <button type=\"button\" data-toggle=\"tooltip\" title=\"";
        // line 6
        echo ($context["button_filter"] ?? null);
        echo "\" onclick=\"\$('#filter-seo').toggleClass('hidden-sm hidden-xs');\" class=\"btn btn-default hidden-md hidden-lg\"><i class=\"fa fa-filter\"></i></button>
        <a href=\"";
        // line 7
        echo ($context["add"] ?? null);
        echo "\" data-toggle=\"tooltip\" title=\"";
        echo ($context["button_add"] ?? null);
        echo "\" class=\"btn btn-primary\"><i class=\"fa fa-plus\"></i></a>
        <button type=\"button\" data-toggle=\"tooltip\" title=\"";
        // line 8
        echo ($context["button_delete"] ?? null);
        echo "\" class=\"btn btn-danger\" onclick=\"confirm('";
        echo ($context["text_confirm"] ?? null);
        echo "') ? \$('#form-url-alias').submit() : false;\"><i class=\"fa fa-trash-o\"></i></button>
      </div>
      <h1>";
        // line 10
        echo ($context["heading_title"] ?? null);
        echo "</h1>
      <ul class=\"breadcrumb\">
        ";
        // line 12
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["breadcrumbs"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["breadcrumb"]) {
            // line 13
            echo "        <li><a href=\"";
            echo twig_get_attribute($this->env, $this->source, $context["breadcrumb"], "href", [], "any", false, false, false, 13);
            echo "\">";
            echo twig_get_attribute($this->env, $this->source, $context["breadcrumb"], "text", [], "any", false, false, false, 13);
            echo "</a></li>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['breadcrumb'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 15
        echo "      </ul>
    </div>
  </div>
  <div class=\"container-fluid\">";
        // line 18
        if (($context["error_warning"] ?? null)) {
            // line 19
            echo "    <div class=\"alert alert-danger alert-dismissible\"><i class=\"fa fa-exclamation-circle\"></i> ";
            echo ($context["error_warning"] ?? null);
            echo "
      <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>
    </div>
    ";
        }
        // line 23
        echo "    ";
        if (($context["success"] ?? null)) {
            // line 24
            echo "    <div class=\"alert alert-success alert-dismissible\"><i class=\"fa fa-check-circle\"></i> ";
            echo ($context["success"] ?? null);
            echo "
      <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>
    </div>
    ";
        }
        // line 28
        echo "    <div class=\"row\">
      <div id=\"filter-seo\" class=\"col-md-3 col-md-push-9 col-sm-12 hidden-sm hidden-xs\">
        <div class=\"panel panel-default\">
          <div class=\"panel-heading\">
            <h3 class=\"panel-title\"><i class=\"fa fa-filter\"></i> ";
        // line 32
        echo ($context["text_filter"] ?? null);
        echo "</h3>
          </div>
          <div class=\"panel-body\">
            <div class=\"form-group\">
              <label class=\"control-label\" for=\"input-query\">";
        // line 36
        echo ($context["entry_query"] ?? null);
        echo "</label>
              <input type=\"text\" name=\"filter_query\" value=\"";
        // line 37
        echo ($context["filter_query"] ?? null);
        echo "\" placeholder=\"";
        echo ($context["entry_query"] ?? null);
        echo "\" id=\"input-query\" class=\"form-control\" />
            </div>
            <div class=\"form-group\">
              <label class=\"control-label\" for=\"input-keyword\">";
        // line 40
        echo ($context["entry_keyword"] ?? null);
        echo "</label>
              <input type=\"text\" name=\"filter_keyword\" value=\"";
        // line 41
        echo ($context["filter_keyword"] ?? null);
        echo "\" placeholder=\"";
        echo ($context["entry_keyword"] ?? null);
        echo "\" id=\"input-keyword\" class=\"form-control\" />
            </div>
            <div class=\"form-group\">
              <label class=\"control-label\" for=\"input-store\">";
        // line 44
        echo ($context["entry_store"] ?? null);
        echo "</label>
              <select name=\"filter_store_id\" id=\"input-store\" class=\"form-control\">
                <option value=\"\"></option>
                ";
        // line 47
        if ((($context["filter_store_id"] ?? null) == "0")) {
            // line 48
            echo "                <option value=\"0\" selected=\"selected\">";
            echo ($context["text_default"] ?? null);
            echo "</option>
                ";
        } else {
            // line 50
            echo "                <option value=\"0\">";
            echo ($context["text_default"] ?? null);
            echo "</option>
                ";
        }
        // line 51
        echo "                  
                ";
        // line 52
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["stores"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["store"]) {
            // line 53
            echo "                ";
            if ((twig_get_attribute($this->env, $this->source, $context["store"], "store_id", [], "any", false, false, false, 53) == ($context["filter_store_id"] ?? null))) {
                // line 54
                echo "                <option value=\"";
                echo twig_get_attribute($this->env, $this->source, $context["store"], "store_id", [], "any", false, false, false, 54);
                echo "\" selected=\"selected\">";
                echo twig_get_attribute($this->env, $this->source, $context["store"], "name", [], "any", false, false, false, 54);
                echo "</option>
                ";
            } else {
                // line 56
                echo "                <option value=\"";
                echo twig_get_attribute($this->env, $this->source, $context["store"], "store_id", [], "any", false, false, false, 56);
                echo "\">";
                echo twig_get_attribute($this->env, $this->source, $context["store"], "name", [], "any", false, false, false, 56);
                echo "</option>
                ";
            }
            // line 58
            echo "                ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['store'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 59
        echo "              </select>
            </div>
            <div class=\"form-group\">
              <label class=\"control-label\" for=\"input-language\">";
        // line 62
        echo ($context["entry_language"] ?? null);
        echo "</label>
              <select name=\"filter_language_id\" id=\"input-language\" class=\"form-control\">
                <option value=\"\"></option>
                
                
                  
                
              
              
              
              
              
                  
                  ";
        // line 75
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["languages"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["language"]) {
            // line 76
            echo "                  ";
            if ((twig_get_attribute($this->env, $this->source, $context["language"], "language_id", [], "any", false, false, false, 76) == ($context["filter_language_id"] ?? null))) {
                // line 77
                echo "                  
                  
              
              
              
              
              
                
                  
                
                <option value=\"";
                // line 87
                echo twig_get_attribute($this->env, $this->source, $context["language"], "language_id", [], "any", false, false, false, 87);
                echo "\" selected=\"selected\">";
                echo twig_get_attribute($this->env, $this->source, $context["language"], "name", [], "any", false, false, false, 87);
                echo "</option>
                
                
                  
                
              
              
              
              
              
                  
                  ";
            } else {
                // line 99
                echo "                  
                  
              
              
              
              
              
                
                  
                
                <option value=\"";
                // line 109
                echo twig_get_attribute($this->env, $this->source, $context["language"], "language_id", [], "any", false, false, false, 109);
                echo "\">";
                echo twig_get_attribute($this->env, $this->source, $context["language"], "name", [], "any", false, false, false, 109);
                echo "</option>
                
                
                  
                
              
              
              
              
              
                  
                  ";
            }
            // line 121
            echo "                  ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['language'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 122
        echo "                
                
            
            
            
            
            
              
                
              
              </select>
            </div>
            <div class=\"text-right\">
              <button type=\"button\" id=\"button-filter\" class=\"btn btn-default\"><i class=\"fa fa-filter\"></i> ";
        // line 135
        echo ($context["button_filter"] ?? null);
        echo "</button>
            </div>
          </div>
        </div>
      </div>
      <div class=\"col-md-9 col-md-pull-3 col-sm-12\">
        <div class=\"panel panel-default\">
          <div class=\"panel-heading\">
            <h3 class=\"panel-title\"><i class=\"fa fa-list\"></i> ";
        // line 143
        echo ($context["text_list"] ?? null);
        echo "</h3>
          </div>
          <div class=\"panel-body\">
            <form action=\"";
        // line 146
        echo ($context["delete"] ?? null);
        echo "\" method=\"post\" enctype=\"multipart/form-data\" id=\"form-url-alias\">
              <div class=\"table-responsive\">
                <table class=\"table table-bordered table-hover\">
                  <thead>
                    <tr>
                      <td style=\"width: 1px;\" class=\"text-center\"><input type=\"checkbox\" onclick=\"\$('input[name*=\\'selected\\']').prop('checked', this.checked);\" /></td>
                      <td class=\"text-left\">";
        // line 152
        if ((($context["sort"] ?? null) == "query")) {
            echo "<a href=\"";
            echo ($context["sort_query"] ?? null);
            echo "\" class=\"";
            echo twig_lower_filter($this->env, ($context["order"] ?? null));
            echo "\">";
            echo ($context["column_query"] ?? null);
            echo "</a>";
        } else {
            echo "<a href=\"";
            echo ($context["sort_query"] ?? null);
            echo "\">";
            echo ($context["column_query"] ?? null);
            echo "</a>";
        }
        echo "</td>
                      <td class=\"text-left\">";
        // line 153
        if ((($context["sort"] ?? null) == "keyword")) {
            echo "<a href=\"";
            echo ($context["sort_keyword"] ?? null);
            echo "\" class=\"";
            echo twig_lower_filter($this->env, ($context["order"] ?? null));
            echo "\">";
            echo ($context["column_keyword"] ?? null);
            echo "</a> ";
        } else {
            echo "<a href=\"";
            echo ($context["sort_keyword"] ?? null);
            echo "\">";
            echo ($context["column_keyword"] ?? null);
            echo "</a>";
        }
        echo "</td>
                      <td class=\"text-left\">";
        // line 154
        if ((($context["sort"] ?? null) == "store")) {
            echo "<a href=\"";
            echo ($context["sort_store"] ?? null);
            echo "\" class=\"";
            echo twig_lower_filter($this->env, ($context["order"] ?? null));
            echo "\">";
            echo ($context["column_store"] ?? null);
            echo "</a>";
        } else {
            echo "<a href=\"";
            echo ($context["sort_store"] ?? null);
            echo "\">";
            echo ($context["column_store"] ?? null);
            echo "</a>";
        }
        echo "</td>
                      <td class=\"text-left\">";
        // line 155
        if ((($context["sort"] ?? null) == "language")) {
            echo "<a href=\"";
            echo ($context["sort_language"] ?? null);
            echo "\" class=\"";
            echo twig_lower_filter($this->env, ($context["order"] ?? null));
            echo "\">";
            echo ($context["column_language"] ?? null);
            echo "</a>";
        } else {
            echo "<a href=\"";
            echo ($context["sort_language"] ?? null);
            echo "\">";
            echo ($context["column_language"] ?? null);
            echo "</a>";
        }
        echo "</td>
                      <td class=\"text-right\">";
        // line 156
        echo ($context["column_action"] ?? null);
        echo "</td>
                    </tr>
                  </thead>
                  <tbody>
                  
                  ";
        // line 161
        if (($context["seo_urls"] ?? null)) {
            // line 162
            echo "                  ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["seo_urls"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["seo_url"]) {
                // line 163
                echo "                  <tr>
                    <td class=\"text-center\">";
                // line 164
                if (twig_in_filter(twig_get_attribute($this->env, $this->source, $context["seo_url"], "seo_url_id", [], "any", false, false, false, 164), ($context["selected"] ?? null))) {
                    // line 165
                    echo "                      <input type=\"checkbox\" name=\"selected[]\" value=\"";
                    echo twig_get_attribute($this->env, $this->source, $context["seo_url"], "seo_url_id", [], "any", false, false, false, 165);
                    echo "\" checked=\"checked\" />
                      ";
                } else {
                    // line 167
                    echo "                      <input type=\"checkbox\" name=\"selected[]\" value=\"";
                    echo twig_get_attribute($this->env, $this->source, $context["seo_url"], "seo_url_id", [], "any", false, false, false, 167);
                    echo "\" />
                      ";
                }
                // line 168
                echo "</td>
                    <td class=\"text-left\">";
                // line 169
                echo twig_get_attribute($this->env, $this->source, $context["seo_url"], "query", [], "any", false, false, false, 169);
                echo "</td>
                    <td class=\"text-left\">";
                // line 170
                echo twig_get_attribute($this->env, $this->source, $context["seo_url"], "keyword", [], "any", false, false, false, 170);
                echo "</td>
                    <td class=\"text-left\">";
                // line 171
                echo twig_get_attribute($this->env, $this->source, $context["seo_url"], "store", [], "any", false, false, false, 171);
                echo "</td>
                    <td class=\"text-left\">";
                // line 172
                echo twig_get_attribute($this->env, $this->source, $context["seo_url"], "language", [], "any", false, false, false, 172);
                echo "</td>
                    <td class=\"text-right\"><a href=\"";
                // line 173
                echo twig_get_attribute($this->env, $this->source, $context["seo_url"], "edit", [], "any", false, false, false, 173);
                echo "\" data-toggle=\"tooltip\" title=\"";
                echo ($context["button_edit"] ?? null);
                echo "\" class=\"btn btn-primary\"><i class=\"fa fa-pencil\"></i></a></td>
                  </tr>
                  ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['seo_url'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 176
            echo "                  ";
        } else {
            // line 177
            echo "                  <tr>
                    <td class=\"text-center\" colspan=\"6\">";
            // line 178
            echo ($context["text_no_results"] ?? null);
            echo "</td>
                  </tr>
                  ";
        }
        // line 181
        echo "                    </tbody>
                  
                </table>
              </div>
            </form>
            <div class=\"row\">
              <div class=\"col-sm-6 text-left\">";
        // line 187
        echo ($context["pagination"] ?? null);
        echo "</div>
              <div class=\"col-sm-6 text-right\">";
        // line 188
        echo ($context["results"] ?? null);
        echo "</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script type=\"text/javascript\"><!--
\$('#button-filter').on('click', function() {
\tvar url = 'index.php?route=design/seo_url&user_token=";
        // line 197
        echo ($context["user_token"] ?? null);
        echo "';

\tvar filter_query = \$('input[name=\\'filter_query\\']').val();

\tif (filter_query) {
\t\turl += '&filter_query=' + encodeURIComponent(filter_query);
\t}

\tvar filter_keyword = \$('input[name=\\'filter_keyword\\']').val();

\tif (filter_keyword) {
\t\turl += '&filter_keyword=' + encodeURIComponent(filter_keyword);
\t}

\tvar filter_store_id = \$('select[name=\\'filter_store_id\\']').val();

\tif (filter_store_id) {
\t\turl += '&filter_store_id=' + encodeURIComponent(filter_store_id);
\t}
\t
\tvar filter_language_id = \$('select[name=\\'filter_language_id\\']').val();

\tif (filter_language_id) {
\t\turl += '&filter_language_id=' + encodeURIComponent(filter_language_id);
\t}

\tlocation = url;
});
//--></script> 
</div>
";
        // line 227
        echo ($context["footer"] ?? null);
    }

    public function getTemplateName()
    {
        return "design/seo_url_list.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  534 => 227,  501 => 197,  489 => 188,  485 => 187,  477 => 181,  471 => 178,  468 => 177,  465 => 176,  454 => 173,  450 => 172,  446 => 171,  442 => 170,  438 => 169,  435 => 168,  429 => 167,  423 => 165,  421 => 164,  418 => 163,  413 => 162,  411 => 161,  403 => 156,  385 => 155,  367 => 154,  349 => 153,  331 => 152,  322 => 146,  316 => 143,  305 => 135,  290 => 122,  284 => 121,  267 => 109,  255 => 99,  238 => 87,  226 => 77,  223 => 76,  219 => 75,  203 => 62,  198 => 59,  192 => 58,  184 => 56,  176 => 54,  173 => 53,  169 => 52,  166 => 51,  160 => 50,  154 => 48,  152 => 47,  146 => 44,  138 => 41,  134 => 40,  126 => 37,  122 => 36,  115 => 32,  109 => 28,  101 => 24,  98 => 23,  90 => 19,  88 => 18,  83 => 15,  72 => 13,  68 => 12,  63 => 10,  56 => 8,  50 => 7,  46 => 6,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "design/seo_url_list.twig", "");
    }
}
