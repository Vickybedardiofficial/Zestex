<?php
/*
|--------------------------------------------------------------------------
| Zestex - Social Network Platform.
|--------------------------------------------------------------------------
| Based on: Zestex - The Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav
|--------------------------------------------------------------------------
| Branded by: Vicky Bedardi Yadav
| E-mail: vicktbedardi9@gmail.com
|--------------------------------------------------------------------------
| Copyright (c) Flip Basket Pvt Ltd. All rights reserved. 
|--------------------------------------------------------------------------
*/

namespace App\Actions\Product;

use App\Models\Product;
use App\Actions\Media\DeleteMediaAction;

class DeleteProductAction
{
	private Product $productData;

	public function __construct(Product $productData) {
		$this->productData = $productData;
	}

	public function execute() {
		$this->productData->media()->each(function($mediaItem) {
			(new DeleteMediaAction($mediaItem))->execute();
		});

		$this->productData->delete();
	}
}
