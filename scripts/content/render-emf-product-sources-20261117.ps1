# Render exact original EMF supplier files using Windows GDI+ to inspect visible details.
# This does not touch product database or production assets.
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing
$srcDir = (Get-ChildItem -LiteralPath '\\wsl.localhost\Ubuntu\home\admin01\src\Data\File_DuLieu_SanPham' -Directory | Where-Object { [IO.File]::Exists([IO.Path]::Combine($_.FullName,'DB-760.emf')) } | Select-Object -First 1).FullName
$outDir = '\\wsl.localhost\Ubuntu\home\admin01\src\Web_PTS_PHP\backend\resources\product-gallery\2026-11-17\den-pha-qa-batch-1\emf-converted'
[System.IO.Directory]::CreateDirectory($outDir) | Out-Null
$codes = @('DB-760','DB-7300','DB-7500','DB-71000','JD-18300')
foreach ($code in $codes) {
  $from=[System.IO.Path]::Combine($srcDir,$code+'.emf')
  $to=[System.IO.Path]::Combine($outDir,$code+'-gdi-render.png')
  if (-not [System.IO.File]::Exists($from)) {Write-Output "MISSING $code";continue}
  $img=$null;$bitmap=$null;$graphics=$null
  try {
    $img=[System.Drawing.Image]::FromFile($from)
    $w=[int][Math]::Min(2200,[Math]::Max(1000, $img.Width*3))
    $h=[int][Math]::Min(2200,[Math]::Max(1000, $img.Height*3))
    $bitmap=New-Object System.Drawing.Bitmap($w,$h,[System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $graphics=[System.Drawing.Graphics]::FromImage($bitmap)
    $graphics.Clear([System.Drawing.Color]::White)
    $graphics.InterpolationMode=[System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $graphics.SmoothingMode=[System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $graphics.PixelOffsetMode=[System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $graphics.DrawImage($img,0,0,$w,$h)
    $bitmap.Save($to,[System.Drawing.Imaging.ImageFormat]::Png)
    $bytes=([System.IO.FileInfo]$to).Length
    Write-Output "RENDERED $code native=$($img.Width)x$($img.Height) drawn=$($w)x$($h) bytes=$bytes path=$to"
  } catch {Write-Output "ERROR $code : $_" }
  finally{if($graphics){$graphics.Dispose()};if($bitmap){$bitmap.Dispose()};if($img){$img.Dispose()}}
}
