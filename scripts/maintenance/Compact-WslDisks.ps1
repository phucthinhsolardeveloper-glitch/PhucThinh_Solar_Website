<#
.SYNOPSIS
  Thu nhỏ file ổ ảo (.vhdx) của WSL và Docker Desktop sau khi đã dọn dữ liệu bên trong.

.DESCRIPTION
  Xóa file trong WSL/Docker KHÔNG làm file .vhdx trên Windows nhỏ lại: nó chỉ lớn lên rồi giữ nguyên.
  Script này: (1) xóa bản sao upload thừa và thư mục worktree cũ pts-laravel13 (cần root), (2) trả block trống cho ổ ảo,
  (3) tắt WSL + Docker Desktop, (4) nén từng .vhdx, (5) in dung lượng trước/sau.

  Chạy: mở PowerShell bằng "Run as administrator", rồi
      powershell -ExecutionPolicy Bypass -File \\wsl.localhost\Ubuntu\home\admin01\src\Web_PTS_PHP\scripts\maintenance\Compact-WslDisks.ps1
  (hoặc chép file này ra D:\ProjectBackup rồi chạy ở đó).

  Trước khi chạy, nên chạy trong WSL:   bash scripts/maintenance/clean-docker-wsl.sh --apply
  Không xóa dữ liệu nào: chỉ nén file ổ ảo. Mất khoảng 5-15 phút; web local và Docker dừng trong lúc đó.
  Production (VPS) không bị ảnh hưởng.
#>
[CmdletBinding()]
param(
  [switch]$SkipWslCleanup,   # bỏ qua bước xóa upload thừa / fstrim trong WSL
  [switch]$Yes               # không hỏi xác nhận
)
$ErrorActionPreference = 'Stop'

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) { throw 'Cần chạy PowerShell bằng "Run as administrator".' }

# Tìm mọi ổ ảo WSL: distro thường (đọc từ registry) và của Docker Desktop.
$disks = @()
Get-ChildItem 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Lxss' | ForEach-Object {
  $p = Get-ItemProperty $_.PSPath
  if ($p.BasePath) {
    $base = $p.BasePath -replace '^\\\\\?\\', ''
    Get-ChildItem -Path $base -Filter '*.vhdx' -ErrorAction SilentlyContinue | ForEach-Object { $disks += $_.FullName }
  }
}
$dockerRoot = Join-Path $env:LOCALAPPDATA 'Docker\wsl'
if (Test-Path $dockerRoot) { Get-ChildItem -Path $dockerRoot -Filter '*.vhdx' -Recurse -ErrorAction SilentlyContinue | ForEach-Object { $disks += $_.FullName } }
$disks = $disks | Sort-Object -Unique
if (-not $disks) { throw 'Không tìm thấy file .vhdx nào.' }

function Size-GB($f) { [math]::Round((Get-Item $f).Length / 1GB, 1) }
Write-Host "`nỔ ảo tìm thấy:" -ForegroundColor Cyan
$before = @{}
foreach ($d in $disks) { $before[$d] = Size-GB $d; Write-Host ("  {0,7} GB  {1}" -f $before[$d], $d) }
$freeBefore = [math]::Round((Get-PSDrive -Name ($disks[0].Substring(0,1))).Free / 1GB, 1)

if (-not $Yes) {
  Write-Host "`nSẽ tắt WSL và Docker Desktop trong lúc nén. Không xóa dữ liệu." -ForegroundColor Yellow
  if ((Read-Host 'Gõ y để tiếp tục') -ne 'y') { return }
}

if (-not $SkipWslCleanup) {
  Write-Host "`n[1/4] Dọn trong WSL (bản sao upload thừa của bản thử nghiệm cũ, fstrim)..." -ForegroundColor Cyan
  # Thư mục này do container tạo bằng user www-data nên cần root. Chỉ xóa đúng đường dẫn này.
  wsl.exe -d Ubuntu -u root -- bash -c 'for R in /home/admin01/src/Web_PTS_PHP/deploy/laravel13/runtime/upload /home/admin01/src/pts-laravel13; do [ -d "$R" ] && rm -rf "$R" && echo "  đã xóa $R"; done; fstrim -av 2>/dev/null | sed "s/^/  /"'
}

Write-Host "`n[2/4] Tắt Docker Desktop và WSL..." -ForegroundColor Cyan
Get-Process 'Docker Desktop','com.docker.backend','com.docker.build' -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
wsl.exe --shutdown
Start-Sleep -Seconds 8

Write-Host "`n[3/4] Nén ổ ảo..." -ForegroundColor Cyan
$hasOptimize = [bool](Get-Command Optimize-VHD -ErrorAction SilentlyContinue)
foreach ($d in $disks) {
  Write-Host "  $d"
  if ($hasOptimize) {
    Optimize-VHD -Path $d -Mode Full
  } else {
    # Windows Home / không có Hyper-V: dùng diskpart.
    $tmp = New-TemporaryFile
    @("select vdisk file=`"$d`"", 'attach vdisk readonly', 'compact vdisk', 'detach vdisk', 'exit') | Set-Content $tmp -Encoding ASCII
    diskpart /s $tmp | Out-Null
    Remove-Item $tmp -Force
  }
}

Write-Host "`n[4/4] Kết quả" -ForegroundColor Cyan
$saved = 0
foreach ($d in $disks) {
  $after = Size-GB $d; $saved += ($before[$d] - $after)
  Write-Host ("  {0,7} GB -> {1,7} GB  {2}" -f $before[$d], $after, $d)
}
$freeAfter = [math]::Round((Get-PSDrive -Name ($disks[0].Substring(0,1))).Free / 1GB, 1)
Write-Host ("`nĐã thu hồi khoảng {0} GB. Ổ {1}: trống {2} GB -> {3} GB." -f [math]::Round($saved,1), $disks[0].Substring(0,1), $freeBefore, $freeAfter) -ForegroundColor Green
Write-Host "Mở lại Docker Desktop, rồi trong WSL chạy:  cd ~/src/Web_PTS_PHP && docker compose up -d"
