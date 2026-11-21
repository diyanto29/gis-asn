import openpyxl
import json
import re
import datetime

FILE_PETA = "/Users/diyanto/.gemini/antigravity/brain/e7b28bbc-8443-4565-8e71-ee3f7f7aec23/.user_uploaded/media_1795242882017_04825dda.xlsx"
FILE_DATA = "/Users/diyanto/.gemini/antigravity/brain/e7b28bbc-8443-4565-8e71-ee3f7f7aec23/.user_uploaded/media_1795242882020_45c6e26c.xlsx"

# Koordinat default pusat Indramayu
DEFAULT_LAT = -6.326979
DEFAULT_LNG = 108.326195

# 31 Kecamatan Indramayu pusat koordinat fallback
KECAMATAN_COORDS = {
    "ANJATAN": (-6.342637, 107.955839),
    "ARAHAN": (-6.361972, 108.273241),
    "BALONGAN": (-6.358940, 108.374372),
    "BANGODUA": (-6.503858, 108.295772),
    "BONGAS": (-6.377870, 108.034278),
    "CANTIGI": (-6.343951, 108.260282),
    "CIKEDUNG": (-6.492715, 108.185313),
    "GABUSWETAN": (-6.440273, 108.078094),
    "GANTAR": (-6.513606, 107.972910),
    "HAURGEULIS": (-6.460545, 107.943377),
    "INDRAMAYU": (-6.326979, 108.326195),
    "JATIBARANG": (-6.475805, 108.310577),
    "JUNTINYUAT": (-6.434996, 108.439093),
    "KANDANGHAUR": (-6.355156, 108.105747),
    "KARANGAMPEL": (-6.462653, 108.451981),
    "KEDOKANBUNDER": (-6.500444, 108.412023),
    "KERTASEMAYA": (-6.533048, 108.358685),
    "KRANGKENG": (-6.500523, 108.480152),
    "KROYA": (-6.489560, 108.064518),
    "LELEA": (-6.431326, 108.246408),
    "LOHBENER": (-6.401316, 108.276665),
    "LOSARANG": (-6.396165, 108.197915),
    "PASEKAN": (-6.302774, 108.327110),
    "PATROL": (-6.314215, 107.997396),
    "SINDANG": (-6.333278, 108.318686),
    "SLIYEG": (-6.453972, 108.350145),
    "SUKAGUMIWANG": (-6.566685, 108.316836),
    "SUKRA": (-6.308996, 107.943555),
    "TERISI": (-6.484856, 108.154081),
    "TUKDANA": (-6.541069, 108.285168),
    "WIDASARI": (-6.466002, 108.290163),
}

def parse_coord(coord_str, name=""):
    if coord_str:
        s = str(coord_str).replace("(", "").replace(")", "").strip()
        parts = s.split(",")
        if len(parts) >= 2:
            try:
                lat = float(parts[0].strip())
                lng = float(parts[1].strip())
                if -10.0 <= lat <= 0.0 and 100.0 <= lng <= 120.0:
                    return lat, lng
            except:
                pass
    
    # Fallback matching kecamatan in name
    name_upper = name.upper()
    for kec, (klat, klng) in KECAMATAN_COORDS.items():
        if kec in name_upper:
            return klat, klng
            
    return DEFAULT_LAT, DEFAULT_LNG

def determine_category(name):
    u = name.upper()
    if u.startswith("KECAMATAN"):
        return "Kecamatan"
    elif u.startswith("KELURAHAN"):
        return "Kelurahan"
    elif "PUSKESMAS" in u:
        return "Puskesmas"
    elif "RSUD" in u or "RUMAH SAKIT" in u:
        return "RSUD"
    elif any(k in u for k in ["SEKOLAH", "SD ", "SMP ", "TK ", "PAUD", "SDN"]):
        return "Sekolah"
    elif u.startswith("DINAS"):
        return "Dinas"
    elif u.startswith("BADAN"):
        return "Badan"
    elif u.startswith("SEKRETARIAT"):
        return "Sekretariat"
    elif u.startswith("INSPEKTORAT"):
        return "Inspektorat"
    return "OPD"

def clean_date(d):
    if isinstance(d, (datetime.date, datetime.datetime)):
        return d.strftime("%Y-%m-%d")
    elif isinstance(d, str):
        d = d.strip()
        # Format DD-MM-YYYY or YYYY-MM-DD
        m = re.match(r"^(\d{4})-(\d{2})-(\d{2})", d)
        if m:
            return f"{m.group(1)}-{m.group(2)}-{m.group(3)}"
        m2 = re.match(r"^(\d{2})-(\d{2})-(\d{4})", d)
        if m2:
            return f"{m2.group(3)}-{m2.group(2)}-{m2.group(1)}"
    return None

print("1. Parsing DATA UNIT KERJA...")
wb_data = openpyxl.load_workbook(FILE_DATA, data_only=True)
ws_units = wb_data["DATA UNIT KERJA"]

units = []
for row in list(ws_units.iter_rows(values_only=True))[1:]:
    if not row or not row[1]:
        continue
    no = row[0]
    nama_unit = str(row[1]).strip()
    nama_singkatan = str(row[2]).strip() if row[2] else nama_unit[:50]
    coord_raw = row[3] if len(row) > 3 else None
    
    lat, lng = parse_coord(coord_raw, nama_unit)
    kategori = determine_category(nama_unit)
    
    units.append({
        "nama_unit": nama_unit,
        "nama_singkatan": nama_singkatan,
        "kategori": kategori,
        "kantor_lat": lat,
        "kantor_lng": lng,
    })

print(f"   Total unit kerja: {len(units)}")

print("2. Parsing Peta Jabatan (Formasi & ABK)...")
wb_peta = openpyxl.load_workbook(FILE_PETA, data_only=True)
ws_peta = wb_peta["Peta Jabatan"]

formasi_list = []
for row in list(ws_peta.iter_rows(values_only=True))[1:]:
    if not row or not row[0] or not row[1]:
        continue
    unit_org = str(row[0]).strip()
    jabatan = str(row[1]).strip()
    jenis_raw = str(row[2]).strip().lower() if row[2] else "pelaksana"
    
    # normalize jenis
    if "fungsional" in jenis_raw:
        jenis_jabatan = "fungsional"
    elif "struktural" in jenis_raw:
        jenis_jabatan = "struktural"
    else:
        jenis_jabatan = "pelaksana"
        
    pns = int(row[3]) if row[3] is not None and str(row[3]).isdigit() else 0
    pppk = int(row[4]) if row[4] is not None and str(row[4]).isdigit() else 0
    bezetting = int(row[6]) if row[6] is not None and str(row[6]).lstrip("-").isdigit() else (pns + pppk)
    kebutuhan = int(row[7]) if row[7] is not None and str(row[7]).lstrip("-").isdigit() else 1
    selisih = int(row[8]) if row[8] is not None and str(row[8]).lstrip("-").isdigit() else (bezetting - kebutuhan)
    
    # Kuota lowong: jika selisih < 0, berarti kebutuhan > bezetting (kurang pegawai / ada kursi lowong)
    kuota_lowong = abs(selisih) if selisih < 0 else 0
    status = "terbuka" if kuota_lowong > 0 else "terisi"
    
    formasi_list.append({
        "unit_org": unit_org,
        "nama_jabatan": jabatan,
        "jenis_jabatan": jenis_jabatan,
        "bezetting_riil": bezetting,
        "kebutuhan_abk": kebutuhan,
        "selisih": selisih,
        "kuota_lowong": kuota_lowong,
        "status": status,
    })

print(f"   Total formasi jabatan: {len(formasi_list)}")

print("3. Parsing DATA PNS...")
ws_pns = wb_data["DATA PNS"]

pns_list = []
for row in list(ws_pns.iter_rows(values_only=True))[1:]:
    if not row or not row[1]:
        continue
    nip = str(row[1]).strip()
    nama = str(row[2]).strip() if row[2] else ""
    jabatan = str(row[3]).strip() if row[3] else ""
    tmt_jabatan = clean_date(row[4])
    jenis_raw = str(row[5]).strip().lower() if row[5] else "pelaksana"
    if "fungsional" in jenis_raw:
        jenis_jabatan = "fungsional"
    elif "struktural" in jenis_raw:
        jenis_jabatan = "struktural"
    else:
        jenis_jabatan = "pelaksana"
        
    golongan = str(row[6]).strip() if row[6] else "III/a"
    tmt_golongan = clean_date(row[7])
    unit_unor = str(row[8]).strip() if row[8] else ""
    unit_induk = str(row[9]).strip() if row[9] else ""
    tingkat_pendidikan = str(row[10]).strip() if row[10] else "S-1/Sarjana"
    pendidikan_terakhir = str(row[11]).strip() if row[11] else ""
    alamat = str(row[12]).strip() if row[12] else ""
    tmt_cpns = clean_date(row[13])
    tmt_pns = clean_date(row[14])
    
    # Calculate masa kerja
    masa_kerja = 5
    if tmt_cpns:
        try:
            thn = int(tmt_cpns[:4])
            masa_kerja = max(0, 2026 - thn)
        except:
            pass
            
    # Estimate domisili coordinates from address
    dom_lat, dom_lng = parse_coord(None, alamat)
    
    pns_list.append({
        "nip": nip,
        "nama": nama,
        "jabatan": jabatan,
        "jenis_jabatan": jenis_jabatan,
        "pangkat_golongan": golongan,
        "tmt_golongan": tmt_golongan,
        "tgl_jabatan": tmt_jabatan,
        "tgl_cpns": tmt_cpns,
        "tgl_pns": tmt_pns,
        "masa_kerja_tahun": masa_kerja,
        "tingkat_pendidikan": tingkat_pendidikan,
        "pendidikan_terakhir": pendidikan_terakhir,
        "unit_unor": unit_unor,
        "unit_induk": unit_induk,
        "alamat_detail": alamat,
        "domisili_lat": dom_lat,
        "domisili_lng": dom_lng,
    })

print(f"   Total pegawai PNS: {len(pns_list)}")

output = {
    "units": units,
    "formasi": formasi_list,
    "pns": pns_list
}

with open("database/data/parsed_client_data.json", "w", encoding="utf-8") as f:
    json.dump(output, f, ensure_ascii=False, indent=2)

print("PARSED CLIENT DATA SAVED TO database/data/parsed_client_data.json!")
