<?php
enum Show {
	case DIR;
	case FILE;
	case BLOB;
	case INVALID;
}

$path = isset($_GET['path']) ?
	realpath(html_entity_decode($_GET['path'])) :
	'.';

$button = function($body) use ($path) {
	$p = realpath($path.'/..');

	return <<<"EOT"
		<button
			name="path"
			type="submit"
			value="$p"
			>
			$body
		</button>
	EOT;
};

$show = Show::INVALID;

$mime = mime_content_type($path);
$type = explode('/', $mime)[0];
$stat = stat($path);

$time = function() use ($stat) {
	$fmt = 'l jS F Y h:i:s A';

	$atime = date($fmt, $stat['atime']);
	$mtime = date($fmt, $stat['mtime']);
	$ctime = date($fmt, $stat['ctime']);

	return <<<"EOT"
		<h2>Last access: $atime</h2>
		<h2>Last modification: $mtime</h2>
		<h2>Last change: $ctime</h2>
	EOT;
};

if (is_dir($path))
	$show = Show::DIR;
else if ($type == 'text' and filesize($path) <= 1024 * 10)
	$show = Show::FILE;
else if (is_file($path))
	$show = Show::BLOB;

$show == Show::INVALID and die("`$path` is a invalid file to show");

?>

<style>
ul {
	list-style-type: circle;
	/*
	margin: 0;
	padding: 0;
	*/
}
</style>

<form method="get">

<?php if ($show == Show::DIR): ?>

	<?php $dir = dir($path); ?>

	<h1>Reading: <?= basename($path) ?></h1>
	<?= $time() ?>

	<ul>

	<li><?= $button('..') ?></li>

	<?php while ($entry = $dir->read()): ?>

		<?php
			if ($entry[0] == '.')
				continue;
		
			$file = $entry;
			if (is_dir("$path/$entry"))
				$file .= '/';
		?>

		<li>
			<button
				name="path"
				type="submit"
				value="<?= "$path/$file" ?>"
			>
			<?= $file ?>
			</button>
		</li>

	<?php endwhile ?>

	</ul>

	<?= $dir->close(); ?>

<?php elseif ($show == Show::FILE): ?>

	<h1>File: <?= basename($path) ?></h1>
	<h2>Size: <?= filesize($path) ?></h2>
	<?= $time() ?>
	<?= $button('back to '.basename(dirname($path)).'/') ?>

	<pre><?= htmlspecialchars(file_get_contents($path)) ?></pre>

<?php elseif ($show == Show::BLOB): ?>

	<h1>Blob: <?= basename($path) ?> </h1>
	<h2>Size: <?= filesize($path) ?></h2>
	<h2>Mime type: <?= $mime ?> </h1>
	<?= $time() ?>
	<?= $button('back to '.basename(dirname($path)).'/') ?>

<?php endif ?>

</form>
