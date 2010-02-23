<div id="checkoutCartSteps">
<~ section name=s loop=6 start=1 ~>
    <~ if !$smarty.section.s.first  ~>
        |
    <~/if~>
    <~ if $smarty.section.s.iteration == $step ~>
        <span class="cartStepCurr"><~ $smarty.section.s.iteration ~></span>
    <~ elseif $smarty.section.s.iteration lt $step ~>
        <span class="cartStepPrev"><~ $smarty.section.s.iteration ~></span>
    <~ else ~>
        <span class="cartStepNext"><~ $smarty.section.s.iteration ~></span>
    <~/if~>
<~/section~>
</div>
