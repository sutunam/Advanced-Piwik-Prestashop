{if isset($tc_view_product)}
	{include file=$tc_view_product}
{/if}
{if isset($tc_view_category)}
	{include file=$tc_view_category}
{/if}
{if ( isset($tc_order) && isset($data_products))}
	{include file=$tc_order products=$data_products order=$data_order}
{/if}
{if isset($getVisitorId)}
	<script type="text/javascript">
		{literal}
			var saveVisitorId = function(){
				jQuery.post(
					window.location.href,
					{
						pk_vid: this.getVisitorId()
					},
					function(data){
						console.log('ID sent: ' + data);
					}
				);
			};
			_paq.push([ saveVisitorId ]);
		{/literal}
	</script>
{/if}
{if (isset($tc_basic) OR isset($tc_view_product) OR isset($tc_view_category))}
	{include file=$tc_basic}
{/if}
<script type="text/javascript">
	{if (isset($tc_basic) OR isset($tc_view_product) OR isset($tc_view_category))}
		_paq.push(['trackPageView']);
	{/if}
	{literal}
		(function(){
			var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
			g.type='text/javascript';
			g.defer=true;
			g.async=true;
			g.src=u+'piwik.js';
			s.parentNode.insertBefore(g,s);
		})();
	{/literal}
</script>
