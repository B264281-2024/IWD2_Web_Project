#!/usr/bin/python3

import numpy as np
import seaborn as sns
import matplotlib.pyplot as plt

# Load distance matrix
matrix = np.loadtxt('distance_matrix.txt', skiprows=1, usecols=range(1:))

# Create heatmap
sns.heatmap(matrix, cmap="coolwarm", annot=False)
plt.title("Distance Matrix Heatmap")
plt.savefig("heatmap.png")
plt.show()