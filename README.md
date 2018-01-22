# Post-CH-Odoo-ERP-Magento2-Connector

<p>Swiss Post is consolidating its core business (logistics) by offering integrated software solutions. For a better service it has upgraded its ERP solution, on an Odoo open source framework and in order to offer a more easy onboarding for e-commerce customers, we developed an open source connector between Odoo and Magento.</p>

<p>Potential customers can use the free Magento Commerce extension to integrate the front store with the ERP.</p>

<p>The Magento Commerce Extension will exchange products, stocks, orders, customers and logistics information with the Odoo ERP.</p>

<p>You must be B2B Swiss Post customer to use this module. For user documentation please go to www.epoint.ro. For more information about the services offered by Swiss Post please see post.ch</p>

Commissioned by: Swiss Post AG (Bern/Switzerland)</br>
Developed by: epoint SRL (Timisoara/Romania)</br>
Contributors: Swiss Post, Camptocamp, Brain-Tec

</br>
<h3>Requirements</h3>

- Magento CE: 2.2+
- Php 7.0.2, 7.0.4, 7.0.6-7.0.x, 7.1.x
- Php libraries: php json, php curl

</br>
<h3>Installation:</h3>

<p><b>Step 1:</b> Download the extension</p>
<p><b>Step 2:</b> Unzip the files</p>
<p><b>Step 3:</b> Copy the files to {Magento root}/app/code/</p>
<p><b>Step 4:</b> Flush cache</p>
<p><b>Step 5:</b> Setup the module by navigating to {Magento root} and use the following commands:</p>
<ul>
<li>./bin/magento setup:upgrade</li>
<li>./bin/magento cache:flush</li>
</ul>

</br>
<h3>Usage</h3>

Using the connector shop owners can automate their processes and save costs and reduce human errors by automatically syncing product data and pushing orders to Odoo.

</br>
<h3>Release History</h3>

1.0.0
- Initial public commit

</br>
<h3>Support</h3>
<p>epoint – <a href="mailto:support@epoint.ro" target="_top">support@epoint.ro</a></p>
