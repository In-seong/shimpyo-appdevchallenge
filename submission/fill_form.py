#!/usr/bin/env python3
"""접수양식-작성내용.md 의 본문을 접수양식 DOCX 의 입력칸(콘텐츠 컨트롤)에 채운다.

사용법:  python3 -I fill_form.py
결과물:  쉼표_접수양식.docx
"""
import html
import io
import os
import re
import shutil
import subprocess
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
SRC_DOCX = os.path.join(HERE, "contest-application-consent.docx")
CONTENT = os.path.join(HERE, "접수양식-작성내용.md")
UNPACKED = os.path.join(HERE, "unpacked")
OUT = os.path.join(HERE, "쉼표_접수양식.docx")

# 여러 줄을 받아야 하는데 양식이 한 줄로 잡아 둔 칸
FORCE_MULTILINE = {"reference_files", "reference_link"}


def load_sections() -> dict:
    text = io.open(CONTENT, encoding="utf-8").read()
    parts = re.split(r"^===\[([a-z_]+)\]===\s*$", text, flags=re.M)
    out = {}
    for i in range(1, len(parts), 2):
        out[parts[i]] = parts[i + 1].strip("\n")
    return out


def runs_for(text: str) -> str:
    """문단 텍스트를 Word 런으로. 줄바꿈은 <w:br/>."""
    if not text.strip():
        return '<w:r><w:t xml:space="preserve"> </w:t></w:r>'
    pieces = []
    for idx, line in enumerate(text.split("\n")):
        if idx:
            pieces.append("<w:r><w:br/></w:r>")
        if line:
            pieces.append(
                '<w:r><w:t xml:space="preserve">%s</w:t></w:r>'
                % html.escape(line, quote=False)
            )
    return "".join(pieces)


def main() -> int:
    sections = load_sections()
    doc_path = os.path.join(UNPACKED, "word", "document.xml")
    d = io.open(doc_path, encoding="utf-8").read()

    filled, skipped = [], []

    for tag, body in sections.items():
        # 해당 태그를 가진 sdt 블록을 통째로 찾는다
        pat = re.compile(
            r"(<w:sdt>(?:(?!</w:sdt>).)*?<w:tag w:val=\"%s\"/>.*?)(<w:sdtContent>)(.*?)(</w:sdtContent>)"
            % re.escape(tag),
            re.S,
        )
        m = pat.search(d)
        if not m:
            skipped.append(tag + " (칸을 찾지 못함)")
            continue
        if not body.strip():
            skipped.append(tag + " (내용 비어 있음 — 건너뜀)")
            continue

        head = m.group(1)
        if tag in FORCE_MULTILINE:
            head = head.replace("<w:text/>", '<w:text w:multiLine="1"/>')

        d = d[: m.start()] + head + m.group(2) + runs_for(body) + m.group(4) + d[m.end():]
        filled.append("%s (%d자)" % (tag, len(body)))

    io.open(doc_path, "w", encoding="utf-8").write(d)

    if os.path.exists(OUT):
        os.remove(OUT)
    subprocess.run(
        ["zip", "-Xqr", OUT, "."], cwd=UNPACKED, check=True
    )

    print("채운 칸:")
    for f in filled:
        print("  ✔", f)
    if skipped:
        print("건너뛴 칸:")
        for s in skipped:
            print("  -", s)
    print("\n결과:", OUT, "(%.0f KB)" % (os.path.getsize(OUT) / 1024))
    return 0


if __name__ == "__main__":
    sys.exit(main())
