import argparse

import json
import csv

try:
	import Image
except:
	from PIL import Image
import reportlab

from reportlab.lib import colors
from reportlab.pdfgen.canvas import Canvas
from reportlab.lib.units import mm
from reportlab.lib.pagesizes import A4, landscape
from reportlab.platypus import Paragraph, Table, TableStyle
from reportlab.lib.styles import getSampleStyleSheet
from reportlab.lib.utils import ImageReader

# Nasty hack: see
# http://stackoverflow.com/questions/2227493/reportlab-and-python-imaging-library-images-from-memory-issue
reportlab.lib.utils.Image= Image

class BadgeGen():

    def __init__(self, festival, output):
        self.canvas = Canvas(output, pagesize=landscape(A4))
        self.canvas.setLineWidth(0.25)

        self.pagemargin = 20*mm

        self.rowheight = (A4[0] - self.pagemargin*2.0)
        self.colwidth = (A4[1] - self.pagemargin*2.0)

        self.index = 0
        self.bps = 1
        self.colour = None

        self.festival = festival

        self.logoimage = None
        if self.festival['logo']:
            self.logoimage = ImageReader(self.festival['logo'])
            (w, h) = self.logoimage.getSize()

            if w > h:
                # Wide image.
                self.logowidth = 60*mm
                self.logoheight = h*60*mm/w
            else:
                # Tall image.
                self.logoheight = 60*mm
                self.logowidth  = w*60*mm/h
        else:
            self.logoheight = self.logowidth = 0

        # Size the festival name to fit
        fontsize = 36
        availableWidth = self.colwidth - self.logowidth - 12*mm
        while (self.canvas.stringWidth(self.festival["name"], "Times-Roman", fontsize) > availableWidth):
            fontsize -= 1
        self.festname_fontsize = fontsize
        if self.logoimage:
            if self.canvas.stringWidth(self.festival["name"], "Times-Roman", fontsize) < (availableWidth - self.logowidth):
                # Centre text on whole badge
                self.festname_x = self.colwidth/2
            else:
                # Centre text between logo and RHS
                self.festname_x = self.colwidth/2 + self.logowidth/2
            self.festname_y = self.rowheight - self.logoheight/2 - fontsize/2
        else:
            self.festname_x = self.colwidth/2
            self.festname_y = self.rowheight - 3*mm - fontsize/2

    def _setup_page(self):

        # Output the colour if needed.
        if self.colour and ((self.index % self.bps) == 0):
            self.canvas.setFont("Times-Bold", 14)
            self.canvas.drawCentredString(A4[0]/2, A4[1] - self.pagemargin/2 - 7, "To be printed on %s paper" % self.colour)


    def Render(self, data, colour=None):
        if self.index == 0:
            self._setup_page()
        elif (self.colour != colour) or (self.index % self.bps == 0):
            # Start a fresh page - either we finished the last one or these need a different colour.
            self.canvas.showPage()
            self.colour = colour
            self._setup_page()

        # Local copy of index within the page.
        index = self.index % self.bps

        # Work out the co-ordinates for this index
        left = (index % 1) * self.colwidth + self.pagemargin
        bottom = (1 - 1 - ((index // 1) % 1)) * self.rowheight + self.pagemargin
        width = self.colwidth
        height = self.rowheight

        # Draw a box around the whole badge
        #self.canvas.setLineWidth(0.25)
        #self.canvas.rect (left, bottom, width, height)

        # Draw the logo, 2mm in from the top left, in a box
        if self.logoimage:
            logobottom = bottom + height - self.logoheight - 2*mm
            self.canvas.drawImage(self.logoimage, left + 2*mm, logobottom, self.logowidth, self.logoheight, preserveAspectRatio=True, anchor='nw')

        # Add the festival name, to the right of the logo
        self.canvas.setFont("Times-Roman", self.festname_fontsize)
        self.canvas.drawCentredString(self.festname_x + left, self.festname_y + bottom, self.festival['name'])

        # Add the registration, just below the middle of the badge.
        fontsize = 96
        reg = data['registration']
        while (self.canvas.stringWidth(reg, "Times-Bold", fontsize) > (width - 4*mm)):
            fontsize -= 1
        self.canvas.setFont("Times-Bold", fontsize)
        self.canvas.drawCentredString(left + width/2, (bottom + height/2)-(fontsize/2)-2*mm, reg)

        fontsize = 32
        volname = data['name']
        while (self.canvas.stringWidth(reg, "Times-Bold", fontsize) > (width - 4*mm)):
            fontsize -= 1
        self.canvas.setFont("Times-Bold", fontsize)
        self.canvas.drawCentredString(left + width/2, (bottom + height/2 - 96)-(fontsize/2)-2*mm, volname)


        # Add the phone, centred, 3mm in from the bottom.
        self.canvas.setFont("Times-Roman", 16)
        self.canvas.drawCentredString(left + width/2, bottom+3*mm, data['phone'])

        dayslist = list(data['days'])
        dayslist = ["Y" if (x == "1") else "" for x in dayslist]
        tabledata = [['F', 'S', 'S', 'M', 'T', 'W', 'T', 'F', 'S', 'S', 'M'],dayslist]
        # Draw the table for the days on site
        t=Table(tabledata,5*mm, 5*mm)
	t.setStyle(TableStyle([ ('FONT',(0,0),(-1,-1),'Courier',8),
				('ALIGN',(0,0),(-1,-1),'CENTER'),
                	        ('VALIGN',(0,0),(-1,-1),'MIDDLE'),
				('INNERGRID', (0,0), (-1,-1), 0.25, colors.black),
                	        ('BOX', (0,0), (-1,-1), 0.25, colors.black),
                	        ]))
	w,h = t.wrap(5*mm,5*mm)
	t.drawOn(self.canvas,left + width - w, bottom+2*mm)


        # Increment the sheet
        self.index = index + 1

    def Save(self):
        self.canvas.save()

parser = argparse.ArgumentParser(description='Generate car passes', fromfile_prefix_chars='@')

festgroup = parser.add_argument_group ('Festival', 'Festival details')
festgroup.add_argument ('--festival-name', help='Festival name', required=True)
festgroup.add_argument ('--festival-logo', help='Festival logo', default=None)

parser.add_argument ('--staff-format', help='Staff list file format', choices=['json','csv'], default='csv')
parser.add_argument ('staff', help='Staff list file name', type=argparse.FileType('r'))
parser.add_argument ('output', help='Destination file name')

args = parser.parse_args()

stafflist = None

if args.staff_format == 'csv':
    stafflist = csv.DictReader(args.staff, fieldnames=['registration','name','phone','days'])
elif args.staff_format == 'json':
    stafflist = json.load(args.staff)

if stafflist:
    f = {'name':args.festival_name, 'logo':args.festival_logo}
    b = BadgeGen(f, args.output)

    for badge in stafflist:
        b.Render(badge)

    b.Save()
