#!/usr/bin/env python3
# DB760 source-accurate VIP1-derived visual design; local staging, no production mutation
from PIL import Image, ImageDraw, ImageFont, ImageFilter, ImageEnhance
from pathlib import Path
import math, json, hashlib
W=1600
ROOT=Path('/home/admin01/src/Web_PTS_PHP')
OUT=ROOT/'docs/10-audits/2026-11-17-db760-vip1-rework-preview'
OUT.mkdir(parents=True,exist_ok=True)
SRC=ROOT/'deploy/laravel13/runtime/upload/product/db-760-9593.png'
CAND=ROOT/'backend/resources/product-gallery/2026-11-17/den-pha-qa-batch-1/candidate-alpha/pts-denpha-db-760-01-qa-20261117.png'
DETAIL=ROOT/'backend/resources/product-gallery/2026-11-17/den-pha-qa-batch-1/detail-candidates/pts-db-760-original-led-detail-qareview.jpg'
REF=Path('/mnt/c/Users/ADMIN/Downloads/VIP1_PHÚC THỊNH SOLAR_12TR5-20261117T050129Z-1-001/VIP1_PHÚC THỊNH SOLAR_12TR5/2- Mã DB7100, DB7200, DB7300/29.png')
REFLOGO=REF.with_name('28.png')
PHOTO=Image.open(SRC).convert('RGBA')
BBOX=PHOTO.getbbox()
PHOTO=PHOTO.crop(BBOX)
DETL=Image.open(DETAIL).convert('RGB')
NAVY=(7,28,70); NAVY2=(4,16,46); BLUE=(9,59,130); GOLD=(212,171,79); WHITE=(250,252,255); MUTED=(196,212,233)
FB='/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'; FR='/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
def font(n,bold=False):return ImageFont.truetype(FB if bold else FR,n)
def gradient(top=(7,29,76),bottom=(4,14,38),W=1600,H=1600):
    a=Image.new('RGB',(W,H));d=ImageDraw.Draw(a)
    for y in range(H):
        k=y/max(1,H-1)
        d.line((0,y,W,y),fill=tuple(int(top[i]*(1-k)+bottom[i]*k) for i in range(3)))
    return a.convert('RGBA')
def scale_cover(a,w,h):
    s=max(w/a.width,h/a.height); b=a.resize((round(a.width*s),round(a.height*s)),Image.Resampling.LANCZOS)
    return b.crop(((b.width-w)//2,(b.height-h)//2,(b.width+w)//2,(b.height+h)//2))
def paste_product(canvas,img,box,shadow=True):
    x,y,w,h=box
    p=img.copy()
    s=min(w/p.width,h/p.height)
    p=p.resize((int(p.width*s),int(p.height*s)),Image.Resampling.LANCZOS)
    xx=x+(w-p.width)//2; yy=y+(h-p.height)//2
    if shadow:
        a=p.getchannel('A').filter(ImageFilter.GaussianBlur(28)).point(lambda v: int(v*0.35))
        sh=Image.new('RGBA',p.size,(0,0,0,0));sh.paste((0,0,0,0), (0,0,p.width,p.height));sh.putalpha(a)
        canvas.alpha_composite(sh,(xx+18,yy+22))
    canvas.alpha_composite(p,(xx,yy))
def text(d,xy,s,n,fill=WHITE,bold=False,anchor=None,stroke_width=0,stroke_fill=None):
    d.text(xy,s,font=font(n,bold),fill=fill,anchor=anchor,stroke_width=stroke_width,stroke_fill=stroke_fill)
def pill(d,xy,wh,title,fill=(15,58,118),outline=GOLD,sz=32,text_col=WHITE):
    x,y=xy;w,h=wh
    d.rounded_rectangle((x,y,x+w,y+h),radius=20,fill=fill,outline=outline,width=3)
    d.text((x+w/2,y+h/2),title,anchor='mm',font=font(sz,True),fill=text_col)
def header(d,section,code=True):
    logo=Image.open(REFLOGO).convert('RGBA').crop((22,13,385,100))
    mask=Image.new('L',logo.size,0);pix=mask.load();fw=18
    for yy in range(logo.height):
        for xx in range(logo.width):
            fade=min(xx,logo.width-1-xx,yy,logo.height-1-yy)
            pix[xx,yy]=max(0,min(255,round(fade*255/fw)))
    logo.putalpha(mask)
    logo=logo.resize((455,110),Image.Resampling.LANCZOS)
    d._image.alpha_composite(logo,(59,10))
    d.line((76,123,510,123),fill=(190,151,65),width=3)
    # QA reference labels live in manifest only, not customer-facing artwork.
def glow_arcs(base):
    o=Image.new('RGBA',(W,W),(0,0,0,0));d=ImageDraw.Draw(o)
    for i in range(11):
        x=790+i*45
        d.arc((x,-240,x+1100,1000),30,140,fill=(17,128,255,70 if i<4 else 28),width=6 if i%3==0 else 2)
    o=o.filter(ImageFilter.GaussianBlur(7))
    base.alpha_composite(o)
def section_rule(d,y):
    d.line((80,y,1520,y),fill=(52,95,146),width=2)

def shot01():
    a=gradient((6,29,87),(3,13,41));glow_arcs(a)
    d=ImageDraw.Draw(a)
    header(d,"VIP1-inspired • Ảnh 01")
    text(d,(800,178),"ĐÈN PHA NĂNG LƯỢNG",69,WHITE,True,anchor='mt')
    text(d,(800,266),"MẶT TRỜI  ORIANA",72,(244,240,220),True,anchor='mt')
    pill(d,(530,366),(540,88),"DB-760  |  60W",sz=50)
    # abstract horizon scene, not a claim of installation
    ground=Image.new('RGBA',(W,450),(4,13,35,0));gd=ImageDraw.Draw(ground)
    for j in range(16):
        y=130+j*20;gd.line((0,y,1600,y),fill=(29,79,140,int(30+j*5)),width=2)
    a.alpha_composite(ground,(0,1120))
    paste_product(a,PHOTO,(430,515,1120,960),True)
    d=ImageDraw.Draw(a)
    for i,(num,label) in enumerate([("01","Đèn pha LED"),("02","Tấm pin rời"),("03","Điều khiển từ xa")]):
        y=670+i*128
        d.ellipse((75,y,158,y+83),fill=(245,250,255),outline=(190,213,238),width=2)
        text(d,(116,y+41),num,31,BLUE,True,anchor='mm')
        text(d,(180,y+20),label,28,WHITE,True)
    d.line((80,1506,1520,1506),fill=GOLD,width=3)

    return a

def shot02():
    a=gradient((7,31,78),(6,17,40))
    ref=Image.open(REF).convert('RGBA')
    scenic=scale_cover(ref,(W),1000).filter(ImageFilter.GaussianBlur(30))
    tint=Image.new('RGBA',(W,1000),(5,20,54,175))
    scenic=Image.alpha_composite(scenic,tint)
    a.alpha_composite(scenic,(0,440))
    d=ImageDraw.Draw(a);header(d,"VIP1-inspired • Ảnh 02")
    text(d,(800,170),"CHIẾU SÁNG LINH HOẠT",72,WHITE,True,anchor='mt')
    text(d,(800,265),"CHO KHÔNG GIAN NGOÀI TRỜI",48,(232,191,91),True,anchor='mt')
    d.rounded_rectangle((70,408,1530,1450),radius=28,fill=(11,35,70,100),outline=(76,124,190),width=3)
    paste_product(a,PHOTO,(470,505,1000,780))
    d=ImageDraw.Draw(a)
    for i,(name,cap) in enumerate([("SÂN NHÀ","Bố trí trên tường"),("LỐI ĐI","Tùy chọn vị trí gá"),("KHUÔN VIÊN","Chiếu sáng khu vực")]):
        x=74+i*492
        d.rounded_rectangle((x,1290,x+466,1420),radius=16,fill=(6,22,51,235),outline=GOLD,width=2)
        text(d,(x+233,1310),name,31,GOLD,True,anchor='mt')
        text(d,(x+233,1368),cap,25,WHITE,False,anchor='mt')

    return a

def shot03():
    a=Image.new('RGBA',(W,W),(251,253,255,255))
    d=ImageDraw.Draw(a)
    d.rectangle((0,0,W,280),fill=NAVY)
    header(d,"VIP1-inspired • Ảnh 03")
    text(d,(800,163),"BỘ SẢN PHẨM DB-760",69,WHITE,True,anchor='mt')
    text(d,(800,252),"Các thành phần nhìn thấy trong ảnh nguồn",30,MUTED,False,anchor='mt')
    # photo source actually includes full kit with black remote and 2 blue battery cells 
    paste_product(a,PHOTO,(85,370,1430,970),True)
    d=ImageDraw.Draw(a)
    parts=[("THÂN ĐÈN","Chóa LED và nhãn DB760"),("TẤM PIN","Tấm pin rời trong bộ ảnh"),("PHỤ KIỆN","Remote, cáp và các món đi kèm")]
    for i,(title,desc) in enumerate(parts):
        x=60+i*500
        d.rounded_rectangle((x,1377,x+480,1534),radius=18,fill=(237,245,253),outline=(137,176,222),width=2)
        text(d,(x+240,1399),title,34,NAVY,True,anchor='mt')
        text(d,(x+240,1466),desc,22,(28,55,88),False,anchor='mt')
    return a

def shot04():
    a=gradient((8,49,115),(3,13,41))
    d=ImageDraw.Draw(a)
    d.rectangle((0,0,800,1600),fill=(20,72,132,95))
    header(d,"VIP1-inspired • Ảnh 04")
    text(d,(800,174),"NGUYÊN LÝ HOẠT ĐỘNG",69,WHITE,True,anchor='mt')
    text(d,(800,266),"NĂNG LƯỢNG MẶT TRỜI",46,GOLD,True,anchor='mt')
    # accurate generic energy flow -- no unsupported promises
    d.ellipse((240,430,470,660),fill=(253,194,79),outline=(255,233,173),width=9)
    for j in range(12):
        t=math.radians(j*30);x=355+160*math.cos(t);y=545+160*math.sin(t)
        x2=355+205*math.cos(t);y2=545+205*math.sin(t)
        d.line((x,y,x2,y2),fill=(255,220,135),width=10)
    # moon right
    d.ellipse((1090,435,1330,675),fill=(226,235,252))
    d.ellipse((1152,395,1400,650),fill=(5,20,52))
    text(d,(355,742),"BAN NGÀY",41,WHITE,True,anchor='mt')
    text(d,(1210,742),"BAN ĐÊM",41,WHITE,True,anchor='mt')
    pill(d,(80,820),(1440,105),"Tấm pin hấp thụ ánh sáng  →  cấp năng lượng cho hệ đèn",sz=29)
    paste_product(a,PHOTO,(135,975,1330,495),False)
    d=ImageDraw.Draw(a)

    return a

def shot05():
    a=Image.new('RGBA',(W,W),(247,250,253,255))
    d=ImageDraw.Draw(a)
    d.rectangle((0,0,W,298),fill=NAVY)
    header(d,"VIP1-inspired • Ảnh 05")
    text(d,(800,163),"CHI TIẾT ĐÈN PHA DB-760",69,WHITE,True,anchor='mt')
    # product detail from the actual EMF reference
    large=DETL.resize((1040,1040),Image.Resampling.LANCZOS)
    a.alpha_composite(large.convert('RGBA'),(40,360))
    d=ImageDraw.Draw(a)
    d.rounded_rectangle((1110,397,1550,1160),radius=22,fill=(234,242,252),outline=(190,211,232),width=3)
    text(d,(1140,447),"MẶT ĐÈN",38,NAVY,True)
    d.line((1140,510,1500,510),fill=GOLD,width=5)
    for i,s in enumerate(["Cụm LED", "Bề mặt phản quang", "Nhãn DB760", "Mặt trước từ ảnh nguồn"]):
        y=570+i*126; d.ellipse((1150,y+4,1184,y+38),fill=BLUE)
        text(d,(1210,y+4),s,23,(32,56,82),True)
    d.rounded_rectangle((90,1419,1510,1545),radius=16,fill=NAVY)
    text(d,(800,1482),"Mặt đèn, LED và nhãn DB760",29,WHITE,True,anchor='mm')
    return a

def shot06():
    a=gradient((7,28,76),(6,18,46))
    d=ImageDraw.Draw(a);header(d,"VIP1-inspired • Ảnh 06")
    text(d,(800,170),"THÔNG TIN BỘ ĐÈN",69,WHITE,True,anchor='mt')
    text(d,(800,256),"ORIANA  |  DB-760  |  60W",44,GOLD,True,anchor='mt')
    # No invented battery capacity / panel watts / dimension / run time / IP.
    for i,(label,desc) in enumerate([
      ("ĐÈN PHA", "Mẫu DB-760, thân đen"),
      ("TẤM PIN", "Tách rời, có dây kết nối"),
      ("REMOTE", "Có trong ảnh sản phẩm"),
      ("LẮP ĐẶT", "Cần đối chiếu phụ kiện thực tế")
    ]):
        x=75+(i%2)*740;y=370+(i//2)*370
        d.rounded_rectangle((x,y,x+710,y+318),radius=21,fill=(15,50,105),outline=(116,168,210),width=2)
        d.rounded_rectangle((x+25,y+26,x+245,y+100),radius=13,fill=(235,242,251))
        text(d,(x+135,y+64),label,26,NAVY,True,anchor='mm')
        text(d,(x+28,y+152),desc,26,WHITE,False)
        d.line((x+30,y+236,x+670,y+236),fill=GOLD,width=4)
    paste_product(a,PHOTO,(260,1122,1080,350),False)
    d=ImageDraw.Draw(a)

    return a

files={}
for idx,fn in enumerate([shot01,shot02,shot03,shot04,shot05,shot06],start=1):
    im=fn()
    dst=OUT/f"ORIANA-DB-760-{idx:02d}-VIP1-CORRECTED-PREVIEW.png"
    im.convert("RGB").save(dst,optimize=True)
    files[dst.name]={"sha256":hashlib.sha256(dst.read_bytes()).hexdigest(),"bytes":dst.stat().st_size}
    print("SAVED",dst,dst.stat().st_size)
thumb=Image.new('RGB',(1500,1070),(238,242,248))
d=ImageDraw.Draw(thumb)
for j,n in enumerate(files):
    z=Image.open(OUT/n);z.thumbnail((470,470))
    x=20+(j%3)*500; y=25+(j//3)*525
    thumb.paste(z,(x,y))
    text(d,(x+8,y+475),f"0{j+1}  |  "+["Hero","Cảnh ứng dụng","Bộ sản phẩm","Nguyên lý","Cận chi tiết","Thông tin bộ đèn"][j],20,NAVY,True)
thumb.save(OUT/"DB-760-6-PREVIEW-CONTACT.jpg",quality=87)
manifest={"sku":"DB-760","brand":"ORIANA","status":"DESIGN_PREVIEW_NOT_PRODUCTION","source_file":str(SRC),"source_sha256":hashlib.sha256(SRC.read_bytes()).hexdigest(),"detail_source":str(DETAIL),"vip1_reference":str(REF),"policy":"Only original photo pixels for physical product; all layouts are 2D compositing. No generative device redesign. Check per-SKU technical claims before publication.","files":files}
(OUT/"manifest.json").write_text(json.dumps(manifest,ensure_ascii=False,indent=2),encoding="utf-8")
print("CONTACT",OUT/"DB-760-6-PREVIEW-CONTACT.jpg")
