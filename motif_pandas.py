#!/usr/bin/python3

import pandas as pd
import re

input_file = "example_motifs_combined.txt"
rows = []
current_seq = ""
has_hit = False
current_motif = {}

# Function to clean invalid values from start and end positions
def clean_position(value):
    # Extract numbers from txt file
    match = re.match(r"(\d+)", value)
    if match:
        return int(match.group(1))
    return None  # Return None if no valid number is found

with open(input_file) as f: 
    for line in f:
        line = line.strip()
        if line.startswith("Results for"): # Loop through each result
            if current_seq and not has_hit:
                rows.append({
                    "Sequence ID": current_seq,
                    "Start": "",
                    "End": "",
                    "Motif": "None found"
                })
            # Reset for new sequence
            current_seq = line.replace("Results for", "").strip()
            has_hit = False
        elif line.startswith("HitCount:"):
            hit_count = int(line.split(":")[1].strip())
            has_hit = hit_count > 0
        elif line.startswith("Start = position"):
            start_pos = re.search(r"Start = position (.+)", line).group(1)
            current_motif["Start"] = clean_position(start_pos)
        elif line.startswith("End = position"):
            end_pos = re.search(r"End = position (.+)", line).group(1)
            current_motif["End"] = clean_position(end_pos)
        elif line.startswith("Motif ="):
            current_motif["Motif"] = line.split("=", 1)[1].strip()
            # Save motif now that we have all the info
            if current_seq and current_motif:
                rows.append({
                    "Sequence ID": current_seq,
                    "Start": current_motif.get("Start", ""),
                    "End": current_motif.get("End", ""),
                    "Motif": current_motif.get("Motif", "")
                })
            current_motif = {}

# Final sequence (if no hits)
if current_seq and not has_hit:
    rows.append({
        "Sequence ID": current_seq,
        "Start": "",
        "End": "",
        "Motif": "None found"
    })

# Save to CSV
df = pd.DataFrame(rows)
df.to_csv("example_motifs_clean.csv", index=False)
print(f"? CSV created with {len(df)} rows.")
