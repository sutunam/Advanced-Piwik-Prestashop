<h2>
	<a target="_blank" href="http://{$piwik_host}">
		{l s='Visit your Piwik Host' mod='piwik'}
	</a>
</h2>
<form method="post" action="">
	<fieldset>
        <legend><img class="middle" alt="" src="../img/admin/cog.gif">{l s='Settings' mod='piwik'}</legend>
        <label>{l s='Piwik Side ID' mod='piwik'}</label>
        <div class="margin-form">
			<input type="text" value="{$piwik_id}" name="PIWIK_ID">
			<p class="clear">{l s='Example: 10' mod='piwik'}</p>
        </div>
        <label>{l s='Piwik Host' mod='piwik'}</label>
        <div class="margin-form">
			<input type="text" value="{$piwik_host}" name="PIWIK_HOST" style="width:180px;">
			<p class="clear">{l s='Example: www.example.com/piwik (without protocol)' mod='piwik'}</p>
        </div>
		<label>{l s='Piwik Token' mod='piwik'}</label>
        <div class="margin-form">
			<input type="text" value="{$piwik_token}" name="PIWIK_TOKEN" style="width:180px;">
			<p class="clear">{l s='Must be either the Super User token_auth, or the token_auth of any user with \'admin\' permission for the website you are recording data against.' mod='piwik'}</p>
        </div>
		<label>{l s='Let Piwik track' mod='piwik'}</label>
        <div class="margin-form">
			<label style="float:none;padding: 0">
				<input type="checkbox" value="basic" name="tracking_types[]"{if in_array('basic',$tracking_types)} checked="checked"{/if}>
				{l s='Basic tracking: page views, actions...' mod='piwik'}
			</label>
			<br />
			<label style="float:none;padding: 0">
				<input type="checkbox" value="order" name="tracking_types[]"{if in_array('order',$tracking_types)} checked="checked"{/if}>
				{l s='Order tracking: orders items, prices...' mod='piwik'}
			</label>
			<br />
			<label style="float:none;padding: 0">
				<input type="checkbox" value="view" name="tracking_types[]"{if in_array('view',$tracking_types)} checked="checked"{/if}>
				{l s='Product/Category view tracking: to generate the useful View/Sale ratio' mod='piwik'}
			</label>
			<br />
			<label style="float:none;padding: 0">
				<input type="checkbox" value="cart" name="tracking_types[]"{if in_array('cart',$tracking_types)} checked="checked"{/if}>
				{l s='Cart tracking: cart items, revenue, what has been abandoned... ' mod='piwik'}
				<span style="color:red;">{l s='(only works with default theme)' mod='piwik'}</span>
			</label>
        </div>
		<input type="submit" class="button" value="{l s='Update' mod='piwik'}" name="submitUpdate">
	</fieldset>
	<br />
	<fieldset>
        <legend><img class="middle" alt="" src="../img/admin/cog.gif">{l s='Order tracking settings' mod='piwik'}</legend>
        <label>{l s='Tracking using' mod='piwik'}</label>
        <div class="margin-form">
			<label style="float:none;padding: 0">
				<input type="radio" value="js" name="tracking_method" class="tracking-method"{if $tracking_method == 'js'} checked="checked"{/if}>
				{l s='Javascript' mod='piwik'}
			</label>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<label style="float:none;padding: 0">
				<input type="radio" value="php" name="tracking_method" class="tracking-method"{if $tracking_method == 'php'} checked="checked"{/if}>
				{l s='PHP' mod='piwik'}
			</label>
        </div>
		<div class="php-tracking-detail"{if $tracking_method == 'js'} style="display:none;"{/if}>
			<label>{l s='Statistic' mod='piwik'}</label>
			<div class="margin-form">
				<ul>
					<li>{l s='Total order tracked:' mod='piwik'} {$total_tracked}</li>
					<li>{l s='Tracked since:' mod='piwik'} {$tracked_since}</li>
				</ul>
			</div>
			<label>{l s='Date of order' mod='piwik'}</label>
			<div class="margin-form">
				<label style="float:none;padding: 0">
					<input type="radio" value="add" name="tracking_date"{if $tracking_date == 'add'} checked="checked"{/if}>
					{l s='Created date' mod='piwik'}
				</label>
				<br />
				<label style="float:none;padding: 0">
					<input type="radio" value="update" name="tracking_date"{if $tracking_date == 'update'} checked="checked"{/if}>
					{l s='Updated date' mod='piwik'}
				</label>
				<p class="clear">{l s='Which date of order do you want to record on Piwik?' mod='piwik'}</p>
			</div>
			<label>{l s='Select status we want to track' mod='piwik'}</label>
			<div class="margin-form">
				{foreach from=$states item=state}
					<label style="float:none;padding: 0">
						<input type="checkbox" value="{$state.id_order_state}" name="states[]"{if in_array($state['id_order_state'],$selected_states)} checked="checked"{/if}>
						{$state.name}
					</label>
					<br />
				{/foreach}
				<p class="clear">{l s='If status of an order is changed to one of these, that order will be recorded as a successful purchase by Piwik' mod='piwik'}</p>
			</div>
		</div>
		<input type="submit" class="button" value="{l s='Update' mod='piwik'}" name="submitUpdate">
	</fieldset>
</form>
<div id="sutu_ad">
	<p>{l s='Ce module a été développé par ' mod='piwik'}<a href="http://www.sutunam.com/" title="Sutunam.com" target="_blank"><strong>Sutunam</strong></a>{l s=', agence web spécialisée E-commerce (Prestashop, Magento)' mod='piwik'}<br/>
		{l s='Contactez-nous par téléphone +33(0) 4 825 331 75 ou par ' mod='piwik'}<a href="mailto:contact@sutunam.com" title="Adresse mail">email.</a><br/>
		{l s='Suivez-nous sur notre compte Twitter : ' mod='piwik'}<a href="https://twitter.com/#!/sutunam" target="_blank">@Sutunam</a></p>
	<div id="logobig"><a href="http://www.sutunam.com/" title="Sutunam.com" target="_blank"><img src="{$module_dir}img/logo2.jpg"></img></a></div>
</div>