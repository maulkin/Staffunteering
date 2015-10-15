import argparse

import json
import csv

try:
	import Image
except:
	from PIL import Image
import reportlab

from reportlab.pdfgen.canvas import Canvas
from reportlab.lib.units import mm
from reportlab.lib.pagesizes import A4
from reportlab.platypus import Paragraph
from reportlab.lib.styles import getSampleStyleSheet
from reportlab.lib.utils import ImageReader

# Nasty hack: see
# http://stackoverflow.com/questions/2227493/reportlab-and-python-imaging-library-images-from-memory-issue
reportlab.lib.utils.Image= Image

class BadgeGen():
    rowcount = 5
    colcount = 2

    def __init__(self, festival, output):
        self.canvas = Canvas(output, pagesize=A4)
        self.canvas.setLineWidth(0.25)

        self.pagemargin = 20*mm

        self.rowheight = (A4[1] - self.pagemargin*2.0)/BadgeGen.rowcount
        self.colwidth = (A4[0] - self.pagemargin*2.0)/BadgeGen.colcount

        self.index = 0
        self.bps = BadgeGen.rowcount * BadgeGen.colcount
        self.colour = None

        self.festival = festival

        self.logoimage = None
        if self.festival['logo']:
            self.logoimage = ImageReader(self.festival['logo'])
            (w, h) = self.logoimage.getSize()

            if w > h:
                # Wide image.
                self.logowidth = 20*mm
                self.logoheight = h*20*mm/w
            else:
                # Tall image.
                self.logoheight = 20*mm
                self.logowidth  = w*20*mm/h
        else:
            self.logoheight = self.logowidth = 0

        # Size the festival name to fit
        fontsize = 18
        availableWidth = self.colwidth - self.logowidth - 4*mm
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
        # Draw cutting lines around edge of page.
        self.canvas.setLineWidth(0.25)
        for col in range(0, self.colcount + 1):
            x = self.pagemargin + col * self.colwidth
            self.canvas.line (x, 0, x, self.pagemargin * 0.9)
            self.canvas.line (x, A4[1], x, A4[1] - self.pagemargin * 0.9)

        for row in range(0, self.rowcount + 1):
            y = self.pagemargin + row * self.rowheight
            self.canvas.line (0, y, self.pagemargin * 0.9, y)
            self.canvas.line (A4[0], y, A4[0] - self.pagemargin * 0.9, y)

        # Output the colour if needed.
        if self.colour and ((self.index % self.bps) == 0):
            self.canvas.setFont("Times-Bold", 14)
            self.canvas.drawCentredString(A4[0]/2, A4[1] - self.pagemargin/2 - 7, "To be printed on %s paper" % self.colour)


    def Render(self, data, colour=None):
        if data['altname']:
            # Double sided badge - check we're in the right place.
            while self.index % self.colcount != 0:
                self.index += 1

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
        left = (index % BadgeGen.colcount) * self.colwidth + self.pagemargin
        bottom = (BadgeGen.rowcount - 1 - ((index // BadgeGen.colcount) % BadgeGen.rowcount)) * self.rowheight + self.pagemargin
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

        # Add the volunteer name, just below the middle of the badge.
        fontsize = 22
        volname = data['name']
        while (self.canvas.stringWidth(volname, "Times-Bold", fontsize) > (width - 4*mm)):
            fontsize -= 1
        self.canvas.setFont("Times-Bold", fontsize)
        self.canvas.drawCentredString(left + width/2, (bottom + height/2)-(fontsize/2)-2*mm, volname)

        # Add the job title, centred, 3mm in from the bottom.
        if data['job']:
            self.canvas.setFont("Times-Roman", 16)
            self.canvas.drawCentredString(left + width/2, bottom+3*mm, data['job'])

        if data['id']:
            self.canvas.setFont("Courier", 8)
            self.canvas.drawRightString(left + width - 1*mm, bottom+3*mm, data['id'])

        self.index = index + 1

        if data['altname']:
            # Call ourselves to render the alternative badge on the other half.
            data['name'] = data['altname']
            data['altname'] = None
            self.Render (data, colour)

    def Save(self):
        # Fill the rest of the sheet with useful blanks.
        blank = {'name':'', 'job':'Volunteer', 'altname':None, 'id':None}
        while self.index % self.bps:
            self.Render (blank, self.colour)

        self.canvas.save()

parser = argparse.ArgumentParser(description='Generate badges', fromfile_prefix_chars='@')

festgroup = parser.add_argument_group ('Festival', 'Festival details')
festgroup.add_argument ('--festival-name', help='Festival name', required=True)
festgroup.add_argument ('--festival-logo', help='Festival logo', default=None)

parser.add_argument ('--staff-format', help='Staff list file format', choices=['json','csv'], default='csv')
parser.add_argument ('staff', help='Staff list file name', type=argparse.FileType('r'))
parser.add_argument ('output', help='Destination file name')

args = parser.parse_args()

stafflist = None

if args.staff_format == 'csv':
    stafflist = csv.DictReader(args.staff, fieldnames=['name','altname','job','id'])
elif args.staff_format == 'json':
    stafflist = json.load(args.staff)

if stafflist:
    f = {'name':args.festival_name, 'logo':args.festival_logo}
    b = BadgeGen(f, args.output)

    for badge in stafflist:
        if not 'altname' in badge:
            badge['altname'] = None
        b.Render(badge)

    b.Save()
