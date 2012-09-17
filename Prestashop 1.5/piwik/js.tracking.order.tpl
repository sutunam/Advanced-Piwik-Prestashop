<script type="text/javascript">
	{foreach from=$products item=product}
		// add product to the order
		_paq.push([
			'addEcommerceItem',
			'{$product.SKU}', // (required) SKU: Product unique identifier
			'{$product.Product}', // (optional) Product name
			{$product.Category}, // (optional) Product category. You can also specify an array of up to 5 categories eg. ["Books", "New releases", "Biography"]
			{$product.Price}, // (recommended) Product price
			'{$product.Quantity}' // (optional, default to 1) Product quantity
		]);
	{/foreach}

	// Specifiy the order details to Piwik server & sends the data to Piwik server
	_paq.push([
			'trackEcommerceOrder',
		'{$order.id}', // (required) Unique Order ID
		{$order.total}, // (required) Order Revenue grand total (includes tax, shipping, and subtracted discount)
		{$order.subtotal}, // (optional) Order sub total (excludes shipping)
		{$order.tax}, // (optional) Tax amount
		{$order.shipping}, // (optional) Shipping amount
		{$order.discount} // (optional) Discount offered (set to false for unspecified parameter)
	]);
</script>