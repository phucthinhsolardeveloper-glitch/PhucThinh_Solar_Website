<div class="photoUpload-zone">
	<div class="photoUpload-detail" id="photoUpload-preview1">
		<?=$func->getImage(['class' => 'rounded', 'size-error' => '250x250x1', 'upload' => $photoDetail['upload'], 'image' => $photoDetail['image'], 'alt' => 'Alt Photo'])?>
	</div>
	<label class="photoUpload-file" id="photo-zone1" for="file-zone1">
		<input type="file" name="file1" id="file-zone1">
		<i class="fas fa-cloud-upload-alt"></i>
		<p class="photoUpload-drop">Kéo và thả hình vào đây</p>
		<p class="photoUpload-or">hoặc</p>
		<p class="photoUpload-choose btn btn-sm bg-gradient-success">Chọn hình</p>
	</label>
	<div class="photoUpload-dimension"><?=$photoDetail['dimension']?></div>
</div>