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

/* tailwind/template/common/cart.twig */
class __TwigTemplate_f502ea41bc7fa6e759070eb8347412f730a928e9771c6598d412c9113890af72 extends \Twig\Template
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
        echo "<div id=\"cart\" class=\"btn-group btn-block\">
    <button
        type=\"button\"
        data-toggle=\"dropdown\"
        data-loading-text=\"";
        // line 5
        echo ($context["text_loading"] ?? null);
        echo "\"
        class=\"btn btn-inverse btn-block btn-lg dropdown-toggle\"
    >
        <i class=\"fa fa-shopping-cart\"></i> <span id=\"cart-total\">";
        // line 8
        echo ($context["text_items"] ?? null);
        echo "</span>
    </button>
    <ul class=\"dropdown-menu pull-right\">
        ";
        // line 11
        if ((($context["products"] ?? null) || ($context["vouchers"] ?? null))) {
            // line 12
            echo "            <li>
                <table class=\"table table-striped\">
                    ";
            // line 14
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["products"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["product"]) {
                // line 15
                echo "                        <tr>
                            <td class=\"text-center\">
                                ";
                // line 17
                if (twig_get_attribute($this->env, $this->source, $context["product"], "thumb", [], "any", false, false, false, 17)) {
                    // line 18
                    echo "                                    <a href=\"";
                    echo twig_get_attribute($this->env, $this->source, $context["product"], "href", [], "any", false, false, false, 18);
                    echo "\">
                                        <img
                                            src=\"";
                    // line 20
                    echo twig_get_attribute($this->env, $this->source, $context["product"], "thumb", [], "any", false, false, false, 20);
                    echo "\"
                                            alt=\"";
                    // line 21
                    echo twig_get_attribute($this->env, $this->source, $context["product"], "name", [], "any", false, false, false, 21);
                    echo "\"
                                            title=\"";
                    // line 22
                    echo twig_get_attribute($this->env, $this->source, $context["product"], "name", [], "any", false, false, false, 22);
                    echo "\"
                                            class=\"img-thumbnail\"
                                        />
                                    </a>
                                ";
                }
                // line 27
                echo "                            </td>
                            <td class=\"text-left\">
                                <a href=\"";
                // line 29
                echo twig_get_attribute($this->env, $this->source, $context["product"], "href", [], "any", false, false, false, 29);
                echo "\">";
                echo twig_get_attribute($this->env, $this->source, $context["product"], "name", [], "any", false, false, false, 29);
                echo "</a> ";
                if (twig_get_attribute($this->env, $this->source, $context["product"], "option", [], "any", false, false, false, 29)) {
                    // line 30
                    echo "                                    ";
                    $context['_parent'] = $context;
                    $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["product"], "option", [], "any", false, false, false, 30));
                    foreach ($context['_seq'] as $context["_key"] => $context["option"]) {
                        // line 31
                        echo "                                        <br />
                                        - <small>";
                        // line 32
                        echo twig_get_attribute($this->env, $this->source, $context["option"], "name", [], "any", false, false, false, 32);
                        echo " ";
                        echo twig_get_attribute($this->env, $this->source, $context["option"], "value", [], "any", false, false, false, 32);
                        echo "</small>
                                    ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_iterated'], $context['_key'], $context['option'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 34
                    echo "                                ";
                }
                // line 35
                echo "                                ";
                if (twig_get_attribute($this->env, $this->source, $context["product"], "recurring", [], "any", false, false, false, 35)) {
                    // line 36
                    echo "                                    <br />
                                    - <small>";
                    // line 37
                    echo ($context["text_recurring"] ?? null);
                    echo " ";
                    echo twig_get_attribute($this->env, $this->source, $context["product"], "recurring", [], "any", false, false, false, 37);
                    echo "</small>
                                ";
                }
                // line 39
                echo "                            </td>
                            <td class=\"text-right\">x ";
                // line 40
                echo twig_get_attribute($this->env, $this->source, $context["product"], "quantity", [], "any", false, false, false, 40);
                echo "</td>
                            <td class=\"text-right\">";
                // line 41
                echo twig_get_attribute($this->env, $this->source, $context["product"], "total", [], "any", false, false, false, 41);
                echo "</td>
                            <td class=\"text-center\">
                                <button
                                    type=\"button\"
                                    onclick=\"cart.remove('";
                // line 45
                echo twig_get_attribute($this->env, $this->source, $context["product"], "cart_id", [], "any", false, false, false, 45);
                echo "');\"
                                    title=\"";
                // line 46
                echo ($context["button_remove"] ?? null);
                echo "\"
                                    class=\"btn btn-danger btn-xs\"
                                >
                                    <i class=\"fa fa-times\"></i>
                                </button>
                            </td>
                        </tr>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['product'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 54
            echo "                    ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["vouchers"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["voucher"]) {
                // line 55
                echo "                        <tr>
                            <td class=\"text-center\"></td>
                            <td class=\"text-left\">";
                // line 57
                echo twig_get_attribute($this->env, $this->source, $context["voucher"], "description", [], "any", false, false, false, 57);
                echo "</td>
                            <td class=\"text-right\">x&nbsp;1</td>
                            <td class=\"text-right\">";
                // line 59
                echo twig_get_attribute($this->env, $this->source, $context["voucher"], "amount", [], "any", false, false, false, 59);
                echo "</td>
                            <td class=\"text-center text-danger\">
                                <button
                                    type=\"button\"
                                    onclick=\"voucher.remove('";
                // line 63
                echo twig_get_attribute($this->env, $this->source, $context["voucher"], "key", [], "any", false, false, false, 63);
                echo "');\"
                                    title=\"";
                // line 64
                echo ($context["button_remove"] ?? null);
                echo "\"
                                    class=\"btn btn-danger btn-xs\"
                                >
                                    <i class=\"fa fa-times\"></i>
                                </button>
                            </td>
                        </tr>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['voucher'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 72
            echo "                </table>
            </li>
            <li>
                <div>
                    <table class=\"table table-bordered\">
                        ";
            // line 77
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["totals"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["total"]) {
                // line 78
                echo "                            <tr>
                                <td class=\"text-right\"><strong>";
                // line 79
                echo twig_get_attribute($this->env, $this->source, $context["total"], "title", [], "any", false, false, false, 79);
                echo "</strong></td>
                                <td class=\"text-right\">";
                // line 80
                echo twig_get_attribute($this->env, $this->source, $context["total"], "text", [], "any", false, false, false, 80);
                echo "</td>
                            </tr>
                        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['total'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 83
            echo "                    </table>
                    <p class=\"text-right\">
                        <a href=\"";
            // line 85
            echo ($context["cart"] ?? null);
            echo "\"><strong><i class=\"fa fa-shopping-cart\"></i> ";
            echo ($context["text_cart"] ?? null);
            echo "</strong></a>&nbsp;&nbsp;&nbsp;<a
                            href=\"";
            // line 86
            echo ($context["checkout"] ?? null);
            echo "\"
                        >
                            <strong><i class=\"fa fa-share\"></i> ";
            // line 88
            echo ($context["text_checkout"] ?? null);
            echo "</strong>
                        </a>
                    </p>
                </div>
            </li>
        ";
        } else {
            // line 94
            echo "            <li>
                <p class=\"text-center\">
                    ";
            // line 96
            echo ($context["text_empty"] ?? null);
            echo "
                </p>
            </li>
        ";
        }
        // line 100
        echo "    </ul>
</div>
";
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/cart.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  270 => 100,  263 => 96,  259 => 94,  250 => 88,  245 => 86,  239 => 85,  235 => 83,  226 => 80,  222 => 79,  219 => 78,  215 => 77,  208 => 72,  194 => 64,  190 => 63,  183 => 59,  178 => 57,  174 => 55,  169 => 54,  155 => 46,  151 => 45,  144 => 41,  140 => 40,  137 => 39,  130 => 37,  127 => 36,  124 => 35,  121 => 34,  111 => 32,  108 => 31,  103 => 30,  97 => 29,  93 => 27,  85 => 22,  81 => 21,  77 => 20,  71 => 18,  69 => 17,  65 => 15,  61 => 14,  57 => 12,  55 => 11,  49 => 8,  43 => 5,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/cart.twig", "");
    }
}
