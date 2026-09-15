import zipfile, re, sys
from xml.etree import ElementTree as ET
NS={'m':'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
    'r':'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
    'pr':'http://schemas.openxmlformats.org/package/2006/relationships'}

class X:
    def __init__(self,path):
        self.z=zipfile.ZipFile(path)
        self.shared=[]
        try:
            root=ET.fromstring(self.z.read('xl/sharedStrings.xml'))
            for si in root.findall('m:si',NS):
                self.shared.append(''.join(t.text or '' for t in si.iter('{%s}t'%NS['m'])))
        except KeyError: pass
        rels={}
        root=ET.fromstring(self.z.read('xl/_rels/workbook.xml.rels'))
        for rel in root.findall('pr:Relationship',NS):
            rels[rel.get('Id')]=rel.get('Target')
        wb=ET.fromstring(self.z.read('xl/workbook.xml'))
        self.sheets=[]
        for sh in wb.find('m:sheets',NS):
            t=rels[sh.get('{%s}id'%NS['r'])]
            if not t.startswith('xl/'): t='xl/'+t.lstrip('/')
            self.sheets.append((sh.get('name'),t))
    def names(self): return [n for n,_ in self.sheets]
    def rows(self,name):
        tgt=dict(self.sheets)[name]
        root=ET.fromstring(self.z.read(tgt))
        out=[]
        for row in root.iter('{%s}row'%NS['m']):
            cells={}
            for c in row.findall('m:c',NS):
                ref=c.get('r'); col=re.match(r'([A-Z]+)',ref).group(1)
                t=c.get('t'); v=c.find('m:v',NS); isel=c.find('m:is',NS)
                if t=='inlineStr' and isel is not None:
                    val=''.join(x.text or '' for x in isel.iter('{%s}t'%NS['m']))
                elif v is None: val=''
                elif t=='s': val=self.shared[int(v.text)]
                else: val=v.text or ''
                if val!='': cells[col]=val
            if cells: out.append(cells)
        return out
    def table(self,name):
        rs=self.rows(name)
        if not rs: return [],[]
        cols=sorted({c for r in rs for c in r}, key=lambda s:(len(s),s))
        grid=[[r.get(c,'') for c in cols] for r in rs]
        return cols,grid
